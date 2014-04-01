<?php

namespace Application\Controller;

use BitWeb\IdCard\Authentication\IdCardAuthentication;
use BitWeb\IdCard\Signing\ConfirmationInfo;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    protected $fileStoragePath = 'data/tmp_files';

    public function indexAction()
    {
        $error = null;
        $file = null;
        $realName = null;
        $ddocFile = null;

        if ($this->getRequest()->isPost() && !IdCardAuthentication::isUserLoggedIn()) {
            $error = 'Don\'t hack. Log in first!';
        }

        try {
            $fileContainer = new Container('file');
            if (isset($fileContainer->systemName) && $fileContainer->systemName !== null) {
                $realName = $fileContainer->realName;
            }
        } catch (\Exception $e) {
            (new Container('file'))->getManager()->getStorage()->clear();
        }

        if ($this->getRequest()->isPost() && $error === null && $realName === null) {
            $uploadedFile = $this->getRequest()->getFiles('file');
            if ($uploadedFile['error'] === 0) {
                if (!is_dir($this->fileStoragePath)) {
                    mkdir($this->fileStoragePath);
                }

                $realName = $uploadedFile['name'];
                $file = time() . basename($realName);
                copy($uploadedFile['tmp_name'], $this->fileStoragePath . DIRECTORY_SEPARATOR . $file);

                // save file data to session
                $fileContainer = new Container('file');
                $fileContainer->systemName = $file;
                $fileContainer->realName = $realName;
            } else {
                $error = 'No file chosen!';
            }
        }

        $view = new ViewModel();
        $view->error = $error;
        $view->file = $realName;
        $view->ddocFile = $ddocFile;

        return $view;
    }

    public function finalizeAction()
    {
        if ($this->getRequest()->isPost()) {

        }

        return $this->response;
    }

    public function logoutAction()
    {
        if (IdCardAuthentication::isUserLoggedIn()) {
            IdCardAuthentication::logout();
        }

        return $this->redirect()->toRoute('home');
    }

    /**
     * @return \Zend\Http\Request
     */
    public function getRequest()
    {
        return parent::getRequest();
    }
}
