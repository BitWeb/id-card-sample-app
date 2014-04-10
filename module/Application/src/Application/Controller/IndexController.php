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
            $fileRealName = $this->params()->fromPost('fileRealName');

            if ($certHex !== null && $certId !== null) {
                if ($file !== null && file_exists($this->fileStoragePath . DIRECTORY_SEPARATOR . $file) && $fileRealName !== null) {
                    try {
                        $signatureService = $this->initSignatureService();

                        $sessionId = $signatureService->startSession($this->fileStoragePath . DIRECTORY_SEPARATOR . $file, $fileRealName);
                        $hash = $signatureService->prepareSignature($sessionId, $certId, $certHex);
                    } catch (IdCardException $e) {
                        $error = $this->formatException($e);
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
                    $signatureService = $this->initSignatureService();

                    $signatureService->finalizeSignature($sessionId, $signatureId, $signature);
                } catch (IdCardException $e) {
                    $error = $this->formatException($e);
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

        if ($this->getRequest()->isGet()) {
            $sessionId = $this->params()->fromQuery('sessionId');
            $file = $this->params()->fromQuery('file');
            $fileRealName = $this->params()->fromQuery('fileRealName');
            if ($sessionId !== null && $fileRealName !== null) {
                $fileLocation = $this->fileStoragePath . DIRECTORY_SEPARATOR . $file;
                if ($file !== null && file_exists($fileLocation)) {
                    try {
                        $signatureService = $this->initSignatureService();

                        $contents = $signatureService->getSignedDoc($sessionId, $fileLocation);
                        $ddocFileName = $this->fileStoragePath . DIRECTORY_SEPARATOR . $fileRealName . '.ddoc';

                        file_put_contents($ddocFileName, $contents);

                        header('Content-Description: File Transfer');
                        header('Content-Type: application/ddoc');
                        header('Content-Disposition: attachment; filename=' . basename($ddocFileName));
                        header('Content-Transfer-Encoding: binary');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($ddocFileName));

                        readfile($ddocFileName);

                        return $this->response;
                    } catch (IdCardException $e) {
                        $error = $this->formatException($e);
                    }
                } else {
                    $error = 'File does not exist!';
                }
            } else {
                $error = 'Invalid session ID';
            }
        } else {
            $error = 'Only GET requests allowed!';
        }

        if ($error !== null) {
            return new JsonModel([
                'success' => false,
                'error' => $error
            ]);
        }

        return $this->getResponse();
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

    protected function formatException(\Exception $exception)
    {
        return $exception->getCode() . ': ' . $exception->getMessage();
    }

    protected function initSignatureService()
    {
        return (new SignatureService())->setWsdl('https://digidocservice.sk.ee/?wsdl')->initSoap();
    }
}
