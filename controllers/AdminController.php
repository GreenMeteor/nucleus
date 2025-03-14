<?php
namespace humhub\modules\nucleus\controllers;

use Yii;
use yii\web\HttpException;
use humhub\modules\admin\components\Controller;
use humhub\modules\nucleus\models\InstallModuleForm;
use humhub\modules\nucleus\services\ModuleInstallerService;

/**
 * AdminController for module installation and management
 */
class AdminController extends Controller
{
    /**
     * Index action showing the form to install modules from GitHub
     */
    public function actionIndex()
    {
        $model = new InstallModuleForm();
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            return $this->actionInstall($model);
        }

        return $this->render('index', [
            'model' => $model,
        ]);
    }
    
    /**
     * Install a module from GitHub
     */
    public function actionInstall($model = null)
    {
        if ($model === null) {
            $model = new InstallModuleForm();
            $model->load(Yii::$app->request->post());
        }

        if (!$model->validate()) {
            return $this->render('index', [
                'model' => $model,
            ]);
        }

        if (!Yii::$app->user->isAdmin()) {
            throw new HttpException(403, 'You need administrator permissions to install modules.');
        }

        $installer = new ModuleInstallerService();
        $result = $installer->installFromGitHub($model->githubUrl, $model->branch);

        if ($result['success']) {
            $this->view->success($result['message']);
        } else {
            $this->view->error($result['message']);
        }

        return $this->redirect(['index']);
    }
}