<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Actors\migrations;

use Besnovatyj\Menu\migrations\m241012_180250_create_menu_table;
use Besnovatyj\Kernel\migration\BaseMigration;

class m241022_195630_insert_menu_demo extends BaseMigration
{

    public function safeUp(): void
    {
        parent::safeUp();

        $this->batchInsert(
            m241012_180250_create_menu_table::TABLE_NAME,
            [
                'id',
                'tree',
                'lft',
                'rgt',
                'depth',
                'name',
                'name_addon',
                'encode',
                'url',
                'active_string',
                'status',
                'slug',
                'sort_order',
            ],
            [
                [1,1,1,44,0,'Главное меню',' ',1,'','','1','main',0],
                [48,1,2,3,1,'Билеты','<span class=\"badge ms-05 secondary-15 secondary-15-hover\"><span class=\"badge-text secondary secondary-hover\">NEW</span></span>',0,'/site/quick-tickets','site/quicktickets','1','quicktickets',0],
                [49,1,6,11,1,'Новости','',0,'/Blog/post/index','Blog','1','blog',0],
                [50,1,7,8,2,'Наши новости','<span class=\"badge ms-05 primary-15 primary-15-hover\"><span class=\"badge-text primary primary-hover\">badge</span></span>',0,'/Blog/news','Blog/news','1','our-news',0],
                [51,1,9,10,2,'Прочие новости','',0,'/Blog/other-news','Blog/other-news','1','blog-other-news',0],
                [52,1,4,5,1,'Актёры','',0,'/Actors/actor/index','Actors/actor/index','1','actors',0],
                [53,1,12,21,1,'Спектакли','',0,'/Performance/performance/index','Performance/performance','1','performance',0],
                [54,1,13,14,2,'Взрослые','',0,'/Performance/performance/taxonomy?slug=repertoire-adult','repertoire-adult','1','repertoire-adult',0],
                [55,1,15,16,2,'Детские','',0,'/Performance/performance/taxonomy?slug=repertoire-children','repertoire-children','1','repertoire-children',0],
                [56,1,17,18,2,'Взрослые (архив)','',0,'/Performance/performance/taxonomy?slug=archive-adult','archive-adult','1','archive-adult',0],
                [57,1,19,20,2,'Детские (архив)','',0,'/Performance/performance/taxonomy?slug=archive-children','archive-children','1','archive-children',0],
                [58,58,1,8,0,'Безопасность','',0,'/Blog/safety','Blog/safety','1','safety-1',0],
                [59,58,2,3,1,'Антитеррористическая безопасность','',0,'/Blog/anti-terrorism-security','Blog/anti-terrorism-security','1','anti-terrorism-security',0],
                [60,58,4,5,1,'Пожарная безопасность','',0,'/Blog/fire-safety','Blog/fire-safety','1','fire-safety',0],
                [61,58,6,7,1,'Гражданская оборона','',0,'/Blog/civil-defense','Blog/civil-defense','1','civil-defense',0],
                [62,1,23,24,2,'Написать нам','',0,'/Contact/contact/index','Contact/contact/','1','contact',0],
                [63,1,37,38,2,'Пушкинская карта','',0,'/Page/page/view?id=17','Page/page/view?id=17','1','pushkin-card',0],
                [64,1,35,36,2,'История театра','',0,'/Page/page/view?id=3','Page/page/view?id=3','1','history',0],
                [65,1,33,34,2,'Прейскурант','',0,'/Page/page/view?id=5','/Page/page/view?id=5','1','price',0],
                [66,1,31,32,2,'Бесплатное посещение','',0,'/Page/page/view?id=8','Page/page/view?id=8','1','free-admission',0],
                [67,1,29,30,2,'Документы','',0,'/Documents/document/index','Documents/document','1','documents',0],
                [68,68,1,2,0,'Карта сайта','',0,'/Sitemap/sitemap/index-html','Sitemap/sitemap/index-html','1','sitemap',0],
                [69,1,39,40,2,'Брендбук','',0,'/Page/page/view?id=9','Page/page/view?id=9','1','brand_book',0],
                [70,1,27,28,2,'Правила посещения театра','',0,'/Page/page/view?id=11','Page/page/view?id=11','1','theatre-visiting-rules',0],
                [71,1,25,26,2,'Часто задаваемые вопросы','',0,'/Page/page/view?id=14','Page/page/view?id=14','1','faq',0],
                [72,1,22,43,1,'О театре','',0,'/Page/page/view?id=2','Page/page/view?id=2','1','about-theatre',0],
                [74,1,41,42,2,'План зала','',0,'/Page/page/view?id=8','','1','auditorium-plan',0],
                [89,89,1,4,0,'222-','',0,'','','0','222',0],
                [90,89,2,3,1,'000','',0,'','','0','000',0],

            ]
        );

    }

    public function safeDown(): void
    {
        // Отменяем действия по умолчанию,
        // parent::safeDown();
    }

}
