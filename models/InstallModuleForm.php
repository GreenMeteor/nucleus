<?php

namespace humhub\modules\nucleus\models;

use Yii;
use yii\base\Model;

/**
 * InstallModuleForm for module installation
 */
class InstallModuleForm extends Model
{
    /**
     * @var string GitHub repository URL
     */
    public $githubUrl;
    
    /**
     * @var string Branch to install (defaults to master)
     */
    public $branch = 'master';
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['githubUrl'], 'required'],
            [['githubUrl'], 'url', 'defaultScheme' => 'https'],
            [['githubUrl'], 'validateGitHubUrl'],
            [['branch'], 'string'],
            [['branch'], 'default', 'value' => 'master'],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'githubUrl' => Yii::t('NucleusModule.base', 'GitHub URL'),
            'branch' => Yii::t('NucleusModule.base', 'Branch'),
        ];
    }
    
    /**
     * Validates a GitHub URL
     */
    public function validateGitHubUrl($attribute, $params)
    {
        if (!preg_match('/^https?:\/\/github\.com\/[\w-]+\/[\w-]+(\/?|\/.+)$/', $this->$attribute)) {
            $this->addError($attribute, Yii::t('NucleusModule.base', 'Please enter a valid GitHub repository URL.'));
        }
    }
}