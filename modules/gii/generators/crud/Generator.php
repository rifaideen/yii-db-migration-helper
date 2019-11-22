<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\modules\gii\generators\crud;

use yii\gii\CodeFile;

/**
 * Generates CRUD
 *
 * @property array $columnNames Model column names. This property is read-only.
 * @property string $controllerID The controller ID (without the module ID prefix). This property is
 * read-only.
 * @property string $nameAttribute This property is read-only.
 * @property array $searchAttributes Searchable attributes. This property is read-only.
 * @property bool|\yii\db\TableSchema $tableSchema This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Generator extends \yii\gii\Generator
{
    public $modelClass;
    public $controllerClass;
    public $generatorPath = '/Applications/XAMPP/xamppfiles/htdocs/hapijs/src/main/';

    public $indexPath = '/api/<model>';
    public $viewPath = '/api/<model>/{id}';
    public $createPath = '/api/<model>';
    public $updatePath = '/api/<model>/{id}';
    public $deletePath = '/api/<model>/{id}';
    public $isPublicAPI = false;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'API CRUD Generator';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'This generator generates a API controller and views that implement CRUD (Create, Read, Update, Delete)
            operations for the specified data model.';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['controllerClass', 'modelClass', 'indexPath', 'viewPath', 'createPath', 'updatePath', 'deletePath'], 'filter', 'filter' => 'trim'],
            [['modelClass', 'controllerClass', 'indexPath', 'viewPath', 'createPath', 'updatePath', 'deletePath'], 'required'],
            [['isPublicAPI'], 'boolean']
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'modelClass' => 'Model Path',
            'generatorPath' => 'API app base path',
            'controllerClass' => 'Controller Namespace',
            'viewPath' => 'View API path',
            'createPath' => 'Create API path',
            'updatePath' => 'Update API path',
            'deletePath' => 'Delete API path',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'modelClass' => 'Relative path of model from a controller file, e.g., <code>.\..\..\models\users</code>.',
            'indexPath' => 'Index endpoint path, replace <model> with desired name.',
            'viewPath' => 'View endpoint path, replace <model> with desired name.',
            'createPath' => 'Create endpoint path, replace <model> with desired name.',
            'updatePath' => 'Update endpoint path, replace <model> with desired name.',
            'deletePath' => 'Delete endpoint path, replace <model> with desired name.',
            'controllerClass' => 'This is the namespace of the controller going to generate, e.g, <code>app\controllers\users</code>',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function requiredTemplates()
    {
        return ['index-js.php', 'view-js.php'];
    }

    /**
     * {@inheritdoc}
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), ['generatorPath']);
    }

    

    /**
     * {@inheritdoc}
     */
    public function generate()
    {

        $model = explode('/', $this->modelClass);
        $params = [
            'modelPath' => $this->modelClass,
            'model' => ucfirst(end($model)),
            'paths' => [
                'index' => $this->indexPath,
                'view' => $this->viewPath,
                'create' => $this->createPath,
                'update' => $this->updatePath,
                'delete' => $this->deletePath
            ],
            'isPublicAPI' => $this->isPublicAPI
        ];

        $files = [
            new CodeFile($this->generatorPath . str_replace('\\', '/', $this->controllerClass) . '/index.js', $this->render('index-js.php', $params)),
            new CodeFile($this->generatorPath . str_replace('\\', '/', $this->controllerClass) . '/view.js', $this->render('view-js.php', $params))
        ];

        return $files;
    }
}
