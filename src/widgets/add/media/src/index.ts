/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * Виджет добавления пункта меню.
 *
 * Инициализируется на всех элементах `.add-menu-item-widget` на странице.
 * Перехватывает отправку формы, делает POST на endpoint через Fetch API,
 * отображает серверные ошибки инлайн и вызывает глобальный `showAlert()`.
 */

// showAlert() загружается через backend\widgets\alert\AlertAsset (simpleAlert.js)
declare global {
    interface Window {
        showAlert?: (config: {
            message: string;
            type: 'success' | 'error' | 'warning' | 'info';
            duration?: number;
        }) => void;
    }
}

interface WidgetConfig {
    readonly endpoint: string;
    readonly csrfHeaders: Record<string, string>;
}

interface CreateSuccessResponse {
    readonly success: true;
}

interface CreateErrorResponse {
    readonly success: false;
    readonly errors?: Record<string, string[]>;
    readonly message: string;
}

type CreateResponse = CreateSuccessResponse | CreateErrorResponse;

class AddMenuItemWidget {
    private readonly modalEl: HTMLElement;
    private readonly form: HTMLFormElement;
    private readonly config: WidgetConfig;
    private isSubmitting = false;

    constructor(modalEl: HTMLElement) {
        const configJson = modalEl.dataset['config'];
        if (!configJson) {
            throw new Error('AddMenuItemWidget: отсутствует атрибут data-config');
        }
        this.config = JSON.parse(configJson) as WidgetConfig;

        const form = modalEl.querySelector<HTMLFormElement>('form');
        if (form === null) {
            throw new Error('AddMenuItemWidget: <form> не найдена внутри модального окна');
        }

        this.modalEl = modalEl;
        this.form = form;
        this.bindEvents();
    }

    private bindEvents(): void {
        this.form.addEventListener('submit', (e: Event) => {
            e.preventDefault();
            e.stopImmediatePropagation();
            if (!this.isSubmitting) {
                void this.handleSubmit();
            }
        });

        // Очищаем ошибки при каждом открытии модального окна
        this.modalEl.addEventListener('show.bs.modal', () => {
            this.clearErrors();
        });
    }

    private async handleSubmit(): Promise<void> {
        this.isSubmitting = true;
        this.clearErrors();
        this.setLoading(true);

        try {
            const response = await fetch(this.config.endpoint, {
                method: 'POST',
                body: new FormData(this.form),
                headers: this.config.csrfHeaders,
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = (await response.json()) as CreateResponse;

            if (result.success) {
                this.closeModal();
                window.showAlert?.({ message: 'Пункт меню успешно добавлен', type: 'success' });
            } else {
                if (result.errors !== undefined) {
                    this.showFieldErrors(result.errors);
                }
                window.showAlert?.({
                    message: result.message || 'Не удалось добавить пункт меню',
                    type: 'error',
                    duration: 5000,
                });
            }
        } catch (err: unknown) {
            const message = err instanceof Error ? err.message : 'Неизвестная ошибка';
            window.showAlert?.({ message: `Ошибка: ${message}`, type: 'error', duration: 0 });
        } finally {
            this.isSubmitting = false;
            this.setLoading(false);
        }
    }

    /**
     * Закрывает Bootstrap-модальное окно через клик по dismiss-кнопке.
     * window.bootstrap недоступен (IIFE без globalName), поэтому используем data API.
     */
    private closeModal(): void {
        this.modalEl.querySelector<HTMLElement>('[data-bs-dismiss="modal"]')?.click();
    }

    /**
     * Отображает серверные ошибки валидации инлайн под полями формы.
     *
     * Yii2 с formName()='' генерирует CSS-классы field-{attribute} (нижний регистр),
     * например: field-name, field-url, field-parentid (camelCase → lowercase).
     */
    private showFieldErrors(errors: Record<string, string[]>): void {
        for (const [field, messages] of Object.entries(errors)) {
            const cssClass = `.field-${field.toLowerCase()}`;
            const wrapper = this.form.querySelector<HTMLElement>(cssClass);
            if (wrapper === null) {
                continue;
            }

            const input = wrapper.querySelector<HTMLElement>('input, select, textarea');
            input?.classList.add('is-invalid');

            let feedback = wrapper.querySelector<HTMLElement>('.invalid-feedback');
            if (feedback === null) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                wrapper.appendChild(feedback);
            }

            feedback.textContent = messages.join(', ');
            feedback.style.display = 'block';
        }
    }

    private clearErrors(): void {
        this.form.querySelectorAll('.is-invalid').forEach((el) => {
            el.classList.remove('is-invalid');
        });
        this.form.querySelectorAll<HTMLElement>('.invalid-feedback').forEach((el) => {
            el.style.display = 'none';
            el.textContent = '';
        });
    }

    private setLoading(loading: boolean): void {
        const btn = this.form.querySelector<HTMLButtonElement>('[type="submit"]');
        if (btn !== null) {
            btn.disabled = loading;
            btn.textContent = loading ? 'Сохранение...' : 'Сохранить';
        }
    }
}

function initAll(): void {
    document.querySelectorAll<HTMLElement>('.add-menu-item-widget').forEach((el) => {
        try {
            new AddMenuItemWidget(el);
        } catch (err: unknown) {
            console.error('AddMenuItemWidget: ошибка инициализации', el, err);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
} else {
    initAll();
}
