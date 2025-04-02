<?php

use yii\helpers\Url;
use humhub\libs\Html;
use humhub\libs\Helpers;
use yii\widgets\ActiveForm;

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
                'class' => 'form-control',
                'id' => 'github-url-input'
            ])->hint(Yii::t('NucleusModule.base', 'Enter the GitHub repository URL of the module you want to install.')); ?>
            
            <div class="dropdown" style="margin-top: 10px;">
                <button class="btn btn-default dropdown-toggle" type="button" id="coreModulesDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                    <?= Yii::t('NucleusModule.base', 'Select HumHub Core Module'); ?>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" id="core-modules-list" aria-labelledby="coreModulesDropdown">
                    <li class="dropdown-header"><?= Yii::t('NucleusModule.base', 'Loading modules...'); ?></li>
                </ul>
            </div>
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
            <li><?= Yii::t('NucleusModule.base', 'Enter the GitHub URL of the core module repository you want to install or select one from the dropdown.'); ?></li>
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

<script <?= Html::nonce() ?>>
$(document).ready(function() {
    // Fetch repositories with humhub-core topic from GitHub API
    function fetchCoreModules() {
        // Show loading indicator
        $('#core-modules-list').html('<li class="dropdown-header">Loading modules...</li>');
        
        $.ajax({
            url: 'https://api.github.com/search/repositories',
            data: {
                q: 'topic:humhub-core',
                sort: 'stars',
                order: 'desc',
                per_page: 100
            },
            success: function(response) {
                var modules = response.items;
                var modulesList = '';
                
                if (modules.length > 0) {
                    // Add each module to the dropdown
                    $.each(modules, function(i, module) {
                        modulesList += '<li><a href="#" class="module-select" data-url="' + module.html_url + '">' + 
                                       module.name + ' <small class="text-muted">(' + module.stargazers_count + ' ★)</small></a></li>';
                    });
                    
                    modulesList += '<li role="separator" class="divider"></li>';
                    modulesList += '<li><a href="https://github.com/topics/humhub-core" target="_blank">' + 
                                   '<?= Yii::t('NucleusModule.base', 'View all HumHub core modules'); ?></a></li>';
                } else {
                    modulesList = '<li class="dropdown-header"><?= Yii::t('NucleusModule.base', 'No modules found'); ?></li>';
                    modulesList += '<li><a href="https://github.com/topics/humhub-core" target="_blank">' + 
                                  '<?= Yii::t('NucleusModule.base', 'Browse HumHub core modules'); ?></a></li>';
                }
                
                $('#core-modules-list').html(modulesList);
                
                // Re-attach event handlers for newly created elements
                $('.module-select').on('click', function(e) {
                    e.preventDefault();
                    var url = $(this).data('url');
                    $('#github-url-input').val(url);
                    fetchBranches(url);
                });
            },
            error: function() {
                // Handle error
                var errorMsg = '<li class="dropdown-header"><?= Yii::t('NucleusModule.base', 'Failed to load modules'); ?></li>';
                errorMsg += '<li><a href="https://github.com/topics/humhub-core" target="_blank">' + 
                           '<?= Yii::t('NucleusModule.base', 'Browse HumHub core modules'); ?></a></li>';
                
                $('#core-modules-list').html(errorMsg);
            }
        });
    }
    
    // New function to fetch branches for a selected repository
    function fetchBranches(repoUrl) {
        // Extract username and repository name from GitHub URL
        var urlParts = repoUrl.replace('https://github.com/', '').split('/');
        var username = urlParts[0];
        var repository = urlParts[1];
        
        // Show loading indicator in branch field
        var $branchField = $('#installmoduleform-branch');
        var originalPlaceholder = $branchField.attr('placeholder');
        $branchField.attr('placeholder', '<?= Yii::t('NucleusModule.base', 'Loading branches...'); ?>');
        
        // Create branches dropdown if it doesn't exist
        if ($('#branch-dropdown-container').length === 0) {
            $branchField.after(
                '<div id="branch-dropdown-container" class="dropdown" style="margin-top: 10px;">' +
                '<button class="btn btn-default dropdown-toggle" type="button" id="branchesDropdown" ' +
                'data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">' +
                '<?= Yii::t('NucleusModule.base', 'Select Branch'); ?>' +
                '<span class="caret"></span>' +
                '</button>' +
                '<ul class="dropdown-menu" id="branches-list" aria-labelledby="branchesDropdown">' +
                '<li class="dropdown-header"><?= Yii::t('NucleusModule.base', 'Loading branches...'); ?></li>' +
                '</ul>' +
                '</div>'
            );
        }
        
        // Fetch branches from GitHub API
        $.ajax({
            url: 'https://api.github.com/repos/' + username + '/' + repository + '/branches',
            success: function(branches) {
                var branchesList = '';
                
                if (branches.length > 0) {
                    // Add each branch to the dropdown
                    $.each(branches, function(i, branch) {
                        branchesList += '<li><a href="#" class="branch-select" data-branch="' + 
                                       branch.name + '">' + branch.name + '</a></li>';
                    });
                    
                    // Add link to view all branches on GitHub
                    branchesList += '<li role="separator" class="divider"></li>';
                    branchesList += '<li><a href="' + repoUrl + '/branches" target="_blank">' + 
                                   '<?= Yii::t('NucleusModule.base', 'View all branches on GitHub'); ?></a></li>';
                } else {
                    branchesList = '<li class="dropdown-header"><?= Yii::t('NucleusModule.base', 'No branches found'); ?></li>';
                }
                
                // Update dropdown content
                $('#branches-list').html(branchesList);
                
                // Reset placeholder
                $branchField.attr('placeholder', originalPlaceholder);
                
                // Attach event handlers for branch selection
                $('.branch-select').on('click', function(e) {
                    e.preventDefault();
                    var branchName = $(this).data('branch');
                    $branchField.val(branchName);
                });
            },
            error: function() {
                // Handle error
                var errorMsg = '<li class="dropdown-header"><?= Yii::t('NucleusModule.base', 'Failed to load branches'); ?></li>';
                $('#branches-list').html(errorMsg);
                
                // Reset placeholder
                $branchField.attr('placeholder', originalPlaceholder);
            }
        });
    }
    
    // Load modules when dropdown is clicked
    $('#coreModulesDropdown').on('click', function() {
        if ($('#core-modules-list .module-select').length === 0) {
            fetchCoreModules();
        }
    });
    
    // Handle module selection from dropdown (for static items)
    $(document).on('click', '.module-select', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        $('#github-url-input').val(url);
        fetchBranches(url);
    });
    
    // Allow manual trigger of branch fetch when URL is entered/changed
    var typingTimer;
    var doneTypingInterval = 1000; // 1 second
    
    $('#github-url-input').on('keyup', function() {
        clearTimeout(typingTimer);
        var url = $(this).val();
        
        if (url.match(/https:\/\/github\.com\/[^\/]+\/[^\/]+/)) {
            typingTimer = setTimeout(function() {
                fetchBranches(url);
            }, doneTypingInterval);
        }
    });
});
</script>
