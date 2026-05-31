<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

class MenuServiceTest extends TestCase
{
    public function testCreateNode()
    {
        $form = new MenuItemForm();
        $form->name = 'Test Menu';
        $form->status = 1;
        $form->parentId = null;

        $service = new MenuService();
        $node = $service->createNode($form);

        $this->assertNotNull($node->id);
        $this->assertEquals('Test Menu', $node->name);
        $this->assertEquals(1, $node->status);
    }
}
