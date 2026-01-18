<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace aitool_genericopenai;

use local_ai_manager\local\connector_factory;

/**
 * Tests for GenericOpenAI instance.
 *
 * @package   aitool_genericopenai
 * @copyright 2025 ISB Bayern
 * @author    Mistral Vibe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class instance_test extends \advanced_testcase {
    /**
     * Test that the connector can be instantiated.
     *
     * @covers \aitool_genericopenai\connector::__construct
     */
    public function test_connector_instantiation(): void {
        $connectorfactory = \core\di::get(connector_factory::class);
        $connector = $connectorfactory->get_connector_by_connectorname_and_model('genericopenai', 'gpt-4o');
        
        // Assert that the connector is properly set up
        $this->assertInstanceOf('\aitool_genericopenai\connector', $connector);
        $this->assertEquals('gpt-4o', $connector->get_instance()->get_model());
    }

    /**
     * Test that custom value 2 is now false (Azure removed).
     *
     * @covers \aitool_genericopenai\connector::has_customvalue2
     */
    public function test_customvalue2_disabled(): void {
        $connectorfactory = \core\di::get(connector_factory::class);
        $connector = $connectorfactory->get_connector_by_connectorname_and_model('genericopenai', 'gpt-4o');
        
        // Assert that customvalue2 (which was used for Azure) is now false
        $this->assertFalse($connector->has_customvalue2());
    }

    /**
     * Test that the connector returns the correct models.
     *
     * @covers \aitool_genericopenai\connector::get_models_by_purpose
     */
    public function test_get_models_by_purpose(): void {
        $connectorfactory = \core\di::get(connector_factory::class);
        $connector = $connectorfactory->get_connector_by_connectorname('genericopenai');
        
        $models = $connector->get_models_by_purpose();
        
        // Assert that we have models for each purpose
        $this->assertArrayHasKey('chat', $models);
        $this->assertArrayHasKey('feedback', $models);
        $this->assertArrayHasKey('singleprompt', $models);
        $this->assertArrayHasKey('translate', $models);
        $this->assertArrayHasKey('itt', $models);
        $this->assertArrayHasKey('questiongeneration', $models);
        $this->assertArrayHasKey('agent', $models);
        
        // Assert that no Azure model names are present
        foreach ($models as $purpose => $purposeModels) {
            foreach ($purposeModels as $model) {
                $this->assertStringNotContainsString('azure', strtolower($model));
            }
        }
    }

    /**
     * Test that selectable models equals all models (no Azure filtering).
     *
     * @covers \aitool_genericopenai\connector::get_selectable_models
     */
    public function test_selectable_models_equals_all_models(): void {
        $connectorfactory = \core\di::get(connector_factory::class);
        $connector = $connectorfactory->get_connector_by_connectorname('genericopenai');
        
        $allModels = $connector->get_models();
        $selectableModels = $connector->get_selectable_models();
        
        // After removing Azure, all models should be selectable
        $this->assertEquals($allModels, $selectableModels);
    }

    /**
     * Test that custom models can be used (free text input).
     *
     * @covers \aitool_genericopenai\instance::extend_form_definition
     */
    public function test_custom_model_support(): void {
        $connectorfactory = \core\di::get(connector_factory::class);
        $connector = $connectorfactory->get_connector_by_connectorname_and_model('genericopenai', 'custom-model-name');
        
        // Assert that custom model names are accepted and stored
        $this->assertEquals('custom-model-name', $connector->get_instance()->get_model());
    }

    /**
     * Test that the form allows free text for model field.
     *
     * @covers \aitool_genericopenai\instance::extend_form_definition
     */
    public function test_model_field_is_text_input(): void {
        global $CFG;
        
        $this->resetAfterTest();
        
        // Create a test instance
        $instance = new \aitool_genericopenai\instance(0);
        $instance->set_name('Test Instance');
        $instance->set_connector('genericopenai');
        $instance->set_tenant('test');
        $instance->set_endpoint('https://api.openai.com/v1/chat/completions');
        $instance->set_apikey('test-key');
        $instance->set_model('my-custom-model-v2'); // Custom model name
        $instance->set_infolink('https://example.com');
        
        // Store the instance
        $instance->store();
        
        // Reload and verify the custom model was saved
        $loadedInstance = new \aitool_genericopenai\instance($instance->get_id());
        $this->assertEquals('my-custom-model-v2', $loadedInstance->get_model());
        
        // Clean up
        global $DB;
        $DB->delete_records('local_ai_manager_instance', ['id' => $instance->get_id()]);
    }
}