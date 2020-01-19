<?php
namespace Faker\Shell;

use Cake\Console\Shell;
use Cake\ORM\Table;
use Exception;
use Faker\Factory;

/**
 * Faker shell command.
 */
class FakerShell extends Shell
{

    /**
     * @var Factory
     */
    protected $Factory;

    /**
     * @var Table
     */
    protected $Table;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * initialize callback
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->Factory = Factory::create();
    }

    /**
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->addOption('count', [
            'help' => 'count',
            'default' => 1
        ]);
        $parser->setDescription([
            'Faker Shell',
        ]);
        return $parser;
    }

    /**
     * main() method.
     *
     * @return bool|int|null Success or error code.
     */
    public function main(...$args)
    {
        $model = array_shift($args);
        if (!$model) {
            return $this->out($this->OptionParser->help());
        }
        
        try {
            $this->attributes = $this->parseArgs($args);
            while ($this->params['count']) {
                $this->faker($model);
                $this->params['count']--;
            }
        } catch (Exception $e)
        {
            $this->getIo()->error($e->getMessage());
        }
    }

    /**
     * @param string $model
     */
    protected function faker($model)
    {
        $this->Table = $this->loadModel($model);

        $data = [];
        foreach ( $this->Table->getSchema()->columns() as $column) {
            $attribute = $this->parseAttribute($column);
            if (!$attribute) {
                continue;
            }

            $data[$column] = $attribute;
        }

        $this->Table->save($this->Table->newEntity($data));
    }

    /**
     * Parse attributes from arguments
     * 
     * @param array $args
     * @return array
     */
    protected function parseArgs($args)
    {
        $attributes = [];
        foreach ($args as $argument) {
            list($field, $rule) = explode(":", $argument);
            $attributes[$field] = $rule;
        }

        return $attributes;
    }

    /**
     * @param string $attribute
     * @return mixed
     */
    protected function parseAttribute($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            return $this->Factory->{$this->attributes[$attribute]};
        }

        $type = $this->Table->getSchema()->getColumn($attribute);
        if (!empty ($type['autoIncrement']) || in_array($attribute, ['created', 'modified'])) {
            return false;
        }
        
        return isset ($this->Factory->$attribute) ? $this->Factory->$attribute : $this->Factory->{$type['type']};
    }

}
