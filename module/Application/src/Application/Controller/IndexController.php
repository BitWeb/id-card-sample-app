<?php

namespace Application\Controller;

use BitWeb\IdCard\Authentication\IdCardAuthentication;
use BitWeb\IdCard\IdCardException;
use BitWeb\IdCard\Signing\SignatureService;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
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
                $file = time() . '-' . basename($realName);
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
        $hash = null;
        $error = null;
        $sessionId = null;

        if ($this->getRequest()->isPost()) {
            /**
             * @var $certHex string
             * @var $certId string
             * @var $file string
             */
            $certHex = $this->params()->fromPost('certHex');
            $certId = $this->params()->fromPost('certId');
            $file = $this->params()->fromPost('file');

            if ($certHex !== null && $certId !== null) {
                if ($file !== null && file_exists($this->fileStoragePath . DIRECTORY_SEPARATOR . $file)) {
                    try {
                        $signatureService = new SignatureService();
                        $signatureService->setWsdl();
                        $signatureService->initSoap();

                        $sessionId = $signatureService->startSession($this->fileStoragePath . DIRECTORY_SEPARATOR . $file);
                        $hash = $signatureService->prepareSignature($sessionId, $certId, $certHex);
                    } catch (IdCardException $e) {
                        $error = $e->getMessage();
                    }
                } else {
                    $error = 'File does not exist!';
                }
            } else {
                $error = 'Invalid certificate ID or hex!';
            }
        } else {
            $error = 'Only POST requests allowed!';
        }

        return new JsonModel([
            'hash' => $hash['SignedInfoDigest'],
            'success' => $error === null,
            'error' => $error,
            'sessionId' => $sessionId,
            'signatureId' => $hash['SignatureId']
        ]);
    }

    public function finalizeAction()
    {
        $error = null;
        $sessionId = null;

        if ($this->getRequest()->isPost()) {
            $signature = $this->params()->fromPost('signature');
            $sessionId = $this->params()->fromPost('sessionId');
            $signatureId = $this->params()->fromPost('signatureId');
            if ($signature !== null && $sessionId !== null && $signatureId !== null) {
                try {
                    $signatureService = new SignatureService();
                    $signatureService->setWsdl();
                    $signatureService->initSoap();

                    $signatureService->finalizeSignature($sessionId, $signatureId, $signature);
                } catch (IdCardException $e) {
                    $error = $e->getMessage();
                }
            } else {
                $error = 'Invalid signature ID, signature or session!';
            }
        } else {
            $error = 'Only POST requests allowed!';
        }

        return new JsonModel([
            'success' => $error === null,
            'error' => $error,
            'sessionId' => $sessionId,
        ]);
    }

    public function downloadAction()
    {
        $error = null;

        if ($this->getRequest()->isPost()) {
            $sessionId = $this->params()->fromPost('sessionId');
            $file = $this->params()->fromPost('file');
            $fileRealName = $this->params()->fromPost('fileRealName');
            if ($sessionId !== null && $fileRealName !== null) {
                if ($file !== null && file_exists($this->fileStoragePath . DIRECTORY_SEPARATOR . $file)) {
                    try {
                        $signatureService = $this->initSignatureService();

                        $contents = $signatureService->getSignedDoc($sessionId);
                        $ddocFileName = $this->fileStoragePath . DIRECTORY_SEPARATOR . time() . '-signed.ddoc';

                        file_put_contents($ddocFileName, $contents);

                        header('Content-Description: File Transfer');
                        header('Content-Type: application/xml; charset=utf-8');
                        header('Content-Disposition: attachment; filename=' . basename($ddocFileName));
                        header('Expires: 0');
                        header('Content-Transfer-Encoding: binary');
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . strlen($contents));

                        readfile($ddocFileName);
                    } catch (IdCardException $e) {
                        $error = $e->getMessage();
                    }
                } else {
                    $error = 'File does not exist!';
                }
            } else {
                $error = 'Invalid session ID';
            }
        } else {
            $error = 'Only POST requests allowed!';
        }

        return new JsonModel([
            'success' => false,
            'error' => $error
        ]);
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

    protected function initSignatureService()
    {
        return (new SignatureService())->setWsdl()->initSoap();
    }
}
