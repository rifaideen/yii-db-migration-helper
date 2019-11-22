<?php
/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $generator yii\gii\generators\crud\Generator */

echo $form->field($generator, 'generatorPath');
echo $form->field($generator, 'modelClass');
echo $form->field($generator, 'controllerClass');
echo $form->field($generator, 'isPublicAPI')->checkbox();
echo $form->field($generator, 'indexPath');
echo $form->field($generator, 'viewPath');
echo $form->field($generator, 'createPath');
echo $form->field($generator, 'updatePath');
echo $form->field($generator, 'deletePath');
