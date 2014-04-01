<?php

namespace Application\Controller;

use BitWeb\IdCard\Authentication\IdCardAuthentication;
use BitWeb\IdCard\Signing\SignatureService;
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

        if ($this->getRequest()->isPost() && $error === null && $realName === null) {
            $uploadedFile = $this->getRequest()->getFiles('file');
            if ($uploadedFile['error'] === 0) {
                if (!is_dir($this->fileStoragePath)) {
                    mkdir($this->fileStoragePath);
                }

                $realName = $uploadedFile['name'];
                $file = time() . basename($realName);
                copy($uploadedFile['tmp_name'], $this->fileStoragePath . DIRECTORY_SEPARATOR . $file);
            } else {
                $error = 'No file chosen!';
            }
        }

        $view = new ViewModel();
        $view->error = $error;
        $view->file = $file;
        $view->realName = $realName;
        $view->ddocFile = $ddocFile;

        return $view;
    }

    public function prepareAction()
    {
        if ($this->getRequest()->isPost()) {
            /**
             * @var $certHex string
             * @var $certId string
             * @var $file string
             */
            $certHex = $this->params('certHex');
            $certId = $this->params('certId');
            $file = $this->params('file');
            if ($certHex !== null && $certId !== null && $file !== null) {
                $signatureService = new SignatureService();
                $signatureService->setWsdl();
                $signatureService->initSoap();

                $sessionId = $signatureService->startSession($this->fileStoragePath . DIRECTORY_SEPARATOR . $file);

                $signatureService->prepareSignature($sessionId, $certId, $certHex);

                $signingSession = new Container('signingSession');
                $signingSession->sessionId = $sessionId;
            }
        }

        return $this->response;
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
