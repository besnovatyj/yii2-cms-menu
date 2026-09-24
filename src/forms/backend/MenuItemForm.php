<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Menu\forms\backend;

use Besnovatyj\TreeManager\Manager\forms\TreeNodeFormInterface;
use Besnovatyj\Validators\SlugValidator;
use Besnovatyj\Menu\entities\Menu;
use yii\base\Model;
use yii\helpers\Inflector;

class MenuItemForm extends Model implements TreeNodeFormInterface
{
    // Глобальные свойства
    public int|string|null $nodeId = null {
        get {
            return $this->nodeId;
        }
    }    // Идентификатор редактируемого узла
    public int|string|null $parentId = null {
        get {
            return $this->parentId;
        }
    }  // Родительское меню данного пункта меню
    public int|string $status = 0 {
        get {
            return $this->status;
        }
    }            // статус активности пункта меню
    // Локальные свойства
    public string $name = '';               // Название пункта меню
    public string $name_addon = '';         // Дополнение к названию пункта меню
    public int $encode = 0;                 // Декодировать ли HTML сущности в названии меню
    public string $url = '';                // ссылка на которую ведёт пункт меню
    public string $slug = '';               // уникальный идентификатор пункта меню
    public string $active_string = '';      // Часть URL, при совпадении с которой пункт меню будет активен
    public int $new_tab = 0;                // Открывать ссылку в новой вкладке (target="_blank")
    public string $css_class = '';          // Дополнительные CSS-классы на элементе <li> пункта меню
    private ?Menu $_menu = null;

    public function __construct(?Menu $menu = null, ?int $parentId = null, $config = [])
    {
        // TODO - решить что-то с этим $parentId, как-то криво это всё
        $this->parentId = $parentId; // При создании в виде дочернего
        if ($menu) {
            $this->nodeId = $menu->id;
            $this->name = $menu->name;
            $this->name_addon = $menu->name_addon;
            $this->encode = $menu->encode;
            $this->url = $menu->url;
            $this->slug = $menu->slug;
            $this->status = $menu->status;
            $this->active_string = $menu->active_string;
            $this->new_tab = (int)$menu->new_tab;
            $this->css_class = (string)$menu->css_class;
            $this->_menu = $menu;
        }
        parent::__construct($config);
    }

    public function beforeValidate(): bool
    {
        $this->status = (int)$this->status;
        $this->nodeId = (int)$this->nodeId;
        $this->parentId = (int)$this->parentId;

        $this->slug = $this->slug ? Inflector::slug($this->slug) : Inflector::slug($this->name);
        return parent::beforeValidate();
    }

    public function rules(): array
    {
        return [
            [['name', 'status'], 'required'],
            [['name', 'name_addon', 'slug', 'url', 'active_string', 'css_class'], 'string', 'max' => 255],
            [['status', 'parentId', 'nodeId'], 'integer'],
            ['status', 'in', 'range' => [0, 1]],
            [['encode', 'new_tab'], 'boolean'],
            // Класс попадает в разметку через Html::addCssClass(), то есть экранируется
            // при рендере — XSS тут невозможен и валидатор нужен не для безопасности,
            // а чтобы опечатка («class="promo"», «.promo») не превратилась в класс,
            // которого нет ни в одной теме, и не искалась потом глазами в стилях.
            // Фильтр раньше проверки: лишние пробелы — не ошибка ввода, их нормализуем молча,
            // а ругаемся только на то, что классом быть не может.
            ['css_class', 'filter', 'filter' => static fn(string $value): string => trim((string)preg_replace('/\s+/', ' ', $value))],
            ['css_class', 'match', 'pattern' => '/^[A-Za-z0-9_\- ]+$/', 'skipOnEmpty' => true,
                'message' => 'Допустимы только латинские буквы, цифры, дефис, подчёркивание и пробел между классами.'],
            ['slug', SlugValidator::class, 'allowLeadingDigit' => true],
            // TODO $root->slug + '#' + $this->slug (свой валидатор)
            [['slug'], 'unique', 'targetClass' => Menu::class, 'filter' => $this->_menu ? ['<>', 'id', $this->_menu->id] : null],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'parentId' => 'Родительский пункт меню',
            'name' => 'Название ссылки',
            'name_addon' => 'Дополнение к названию',
            'encode' => 'Преобразовывать HTML сущности в названии',
            'url' => 'URL адрес пункта меню',
            'status' => 'Статус пункта меню',
            'slug' => 'Slug',
            'active_string' => 'Строка активности при совпадении',
            'new_tab' => 'Открывать в новой вкладке',
            'css_class' => 'CSS-классы пункта меню',
        ];
    }

    /**
     * Подсказки под полями.
     *
     * Только у новых полей: ActiveField подставляет hint автоматически из этого метода,
     * поэтому перечислять здесь остальные атрибуты значило бы молча изменить вид уже
     * привычных форм.
     */
    public function attributeHints(): array
    {
        return [
            'new_tab' => 'Внешние ссылки (билеты, соцсети, сайты партнёров) удобно открывать отдельной вкладкой, чтобы посетитель не терял страницу сайта.',
            'css_class' => 'Классы через пробел, без точки. Попадают на элемент &lt;li&gt; пункта — по ним тема оформляет отдельный пункт (например, кнопку «Билеты»).',
        ];
    }

    public function isNewRecord(): bool
    {
        return $this->_menu !== null;
    }

    // Для того чтобы упростить поиск полей в сервисе TreeControllerTrait
    public function formName(): string
    {
        return '';
    }

}
