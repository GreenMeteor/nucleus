<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use humhub\libs\Helpers;

/**
 * @var $this yii\web\View
 * @var $model humhub\modules\nucleus\models\InstallModuleForm
 */

?>

<div class="panel panel-default">
    <div class="panel-heading">
        <?= Yii::t('NucleusModule.base', '<strong>Nucleus</strong> Settings'); ?>
    </div>
    <div class="panel-body">
        <div class="alert alert-info">
            <?= Yii::t('NucleusModule.base', 'This module allows you to install custom core modules from GitHub directly into the <code>/protected/humhub/modules</code> directory.'); ?>
            <br>
            <br>
            <div class="alert alert-warning">
                <strong><?= Yii::t('NucleusModule.base', 'Warning:'); ?></strong> 
                <?= Yii::t('NucleusModule.base', 'Only install modules from trusted sources. Installing modules can potentially harm your installation if they contain malicious code.'); ?>
            </div>
        </div>

        <?php $form = ActiveForm::begin(['id' => 'install-module-form']); ?>

        <div class="form-group">
            <?= $form->field($model, 'githubUrl')->textInput([
                'placeholder' => 'https://github.com/username/repository',
                'class' => 'form-control'
            ])->hint(Yii::t('NucleusModule.base', 'Enter the GitHub repository URL of the module you want to install.')); ?>
        </div>

        <div class="form-group">
            <?= $form->field($model, 'branch')->textInput([
                'placeholder' => 'master',
                'class' => 'form-control'
            ])->hint(Yii::t('NucleusModule.base', 'Enter the branch name to download (defaults to master).')); ?>
        </div>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('NucleusModule.base', 'Install'), ['class' => 'btn btn-primary', 'data-ui-loader' => '']); ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <?= Yii::t('NucleusModule.base', 'Instructions'); ?>
    </div>
    <div class="panel-body">
        <p>
            <?= Yii::t('NucleusModule.base', 'To use this module:'); ?>
        </p>
        <ol>
            <li><?= Yii::t('NucleusModule.base', 'Enter the GitHub URL of the core module repository you want to install.'); ?></li>
            <li><?= Yii::t('NucleusModule.base', 'Optionally specify a different branch (default is master).'); ?></li>
            <li><?= Yii::t('NucleusModule.base', 'Click the Install button to download and install the module.'); ?></li>
            <li><?= Yii::t('NucleusModule.base', 'The module will be downloaded, extracted, and installed into the core modules directory.'); ?></li>
            <li><?= Yii::t('NucleusModule.base', 'Any migrations included with the module will be automatically applied.'); ?></li>
        </ol>

        <p>
            <strong><?= Yii::t('NucleusModule.base', 'Requirements for the core module:'); ?></strong>
        </p>
        <ul>
            <li><?= Yii::t('NucleusModule.base', 'The module should be properly structured as a HumHub core module.'); ?></li>
            <li><?= Yii::t('NucleusModule.base', 'If the module has migrations, they should be in a "migrations" directory.'); ?></li>
        </ul>
    </div>
</div>