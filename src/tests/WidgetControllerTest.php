<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

class WidgetControllerTest extends TestCase
{
    public function testActionCreate()
    {
        $response = $this->post('/Menu/backend/widget/create', [
            'name' => 'Test Menu',
            'status' => 1,
        ]);

        $this->assertEquals(200, $response->statusCode);
        $data = json_decode($response->content, true);
        $this->assertTrue($data['success']);
        $this->assertNotNull($data['node']['id']);
    }
}
