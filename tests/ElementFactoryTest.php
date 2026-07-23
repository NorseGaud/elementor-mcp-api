<?php

class ElementFactoryTest extends TestCase {

    public function test_generate_id_and_builders() {
        $id = McpApiForElementor\Element_Factory::generate_id();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{8}$/', $id);

        $container = McpApiForElementor\Element_Factory::container(['pad' => 1], [], true);
        $this->assertSame('container', $container['elType']);
        $this->assertTrue($container['isInner']);
        $this->assertSame(1, $container['settings']['pad']);

        $row = McpApiForElementor\Element_Factory::row([]);
        $this->assertSame('row', $row['settings']['flex_direction']);

        $col = McpApiForElementor\Element_Factory::column([], 33);
        $this->assertSame(33.0, $col['settings']['width']['size']);

        $heading = McpApiForElementor\Element_Factory::heading('Hi', 'h3');
        $this->assertSame('heading', $heading['widgetType']);
        $this->assertSame('Hi', $heading['settings']['title']);
        $this->assertSame('h3', $heading['settings']['header_size']);

        $text = McpApiForElementor\Element_Factory::text('<p>x</p>');
        $this->assertSame('text-editor', $text['widgetType']);

        $image = McpApiForElementor\Element_Factory::image(7);
        $this->assertSame(7, $image['settings']['image']['id']);

        $button = McpApiForElementor\Element_Factory::button('Click', '/go');
        $this->assertSame('/go', $button['settings']['link']['url']);

        $this->assertSame('divider', McpApiForElementor\Element_Factory::divider()['widgetType']);
        $spacer = McpApiForElementor\Element_Factory::spacer(40);
        $this->assertSame(40, $spacer['settings']['space']['size']);
        $this->assertSame('icon', McpApiForElementor\Element_Factory::icon()['widgetType']);

        $social = McpApiForElementor\Element_Factory::social_icons(['twitter' => 'https://x.com']);
        $this->assertCount(1, $social['settings']['social_icon_list']);

        $nav = McpApiForElementor\Element_Factory::nav_menu('primary');
        $this->assertSame('primary', $nav['settings']['menu']);

        $form = McpApiForElementor\Element_Factory::form('Contact', [
            ['field_label' => 'Email', 'field_type' => 'email'],
        ], 'a@b.c');
        $this->assertSame('form', $form['widgetType']);
        $this->assertSame('a@b.c', $form['settings']['email_to']);

        $hero = McpApiForElementor\Element_Factory::hero('Title', 3);
        $this->assertSame('container', $hero['elType']);
        $this->assertSame('Title', $hero['elements'][0]['settings']['title']);

        $left = McpApiForElementor\Element_Factory::content_row('T', 'Body', 2, true);
        $right = McpApiForElementor\Element_Factory::content_row('T', 'Body', 2, false);
        $this->assertSame('image', $left['elements'][0]['elements'][0]['widgetType']);
        $this->assertSame('heading', $right['elements'][0]['elements'][0]['widgetType']);
    }

    public function test_reassign_ids() {
        $tree = [
            'id' => 'aaaa1111',
            'elType' => 'container',
            'elements' => [
                ['id' => 'bbbb2222', 'elType' => 'widget', 'widgetType' => 'heading', 'elements' => []],
            ],
        ];
        McpApiForElementor\Element_Factory::reassign_ids($tree);
        $this->assertNotSame('aaaa1111', $tree['id']);
        $this->assertNotSame('bbbb2222', $tree['elements'][0]['id']);
    }
}
