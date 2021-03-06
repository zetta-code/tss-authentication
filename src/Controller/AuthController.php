<?php
/**
 * @link      http://github.com/zetta-repo/tss-authentication for the canonical source repository
 * @copyright Copyright (c) 2016 Zetta Code
 */

namespace TSS\Authentication\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use TSS\Authentication\Entity\CredentialInterface;
use TSS\Authentication\Entity\RoleInterface;
use TSS\Authentication\Entity\UserInterface;
use TSS\Authentication\Form\PasswordChangeForm;
use TSS\Authentication\Form\RecoverForm;
use TSS\Authentication\Form\SigninForm;
use TSS\Authentication\Form\SignupForm;
use Zend\Authentication\AuthenticationService;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AuthController extends AbstractActionController
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var AuthenticationService
     */
    protected $authenticationService;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $routes;

    /**
     * @var array
     */
    protected $templates;

    /**
     * @var string
     */
    protected $layoutView;

    /**
     * AuthController constructor.
     * @param EntityManager $entityManager
     * @param AuthenticationService $authenticationService
     * @param TranslatorInterface $translator
     * @param array $config
     */
    public function __construct(EntityManager $entityManager, AuthenticationService $authenticationService, TranslatorInterface $translator, array $config)
    {
        $this->entityManager = $entityManager;
        $this->authenticationService = $authenticationService;
        $this->translator = $translator;
        $this->setConfig($config);
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {

        $this->routes = $config['tss']['authentication']['routes'];
        $this->templates = $config['tss']['authentication']['templates'];
        $this->config = $config['tss']['authentication']['config'];

        $this->layoutView = $config['tss']['authentication']['layout'];
    }

    public function signinAction()
    {
        if ($this->identity()) {
            return $this->redirect()->toRoute($this->routes['redirect']['name'], $this->routes['redirect']['params'], $this->routes['redirect']['options'], $this->routes['redirect']['reuseMatchedParams']);
        }

        $form = new SigninForm();
        $form->setAttribute('action', $this->url()->fromRoute($this->routes['authenticate']['name'], $this->routes['authenticate']['params'], $this->routes['authenticate']['options'], $this->routes['authenticate']['reuseMatchedParams']));
        $form->prepare();

        $viewModel = new ViewModel([
            'form' => $form,
            'routes' => $this->routes
        ]);

        $viewModel->setTemplate($this->templates['signin']);
        $this->layout($this->layoutView);

        return $viewModel;
    }

    public function authenticateAction()
    {
        if ($this->identity()) {
            return $this->redirect()->toRoute($this->routes['redirect']['name'], $this->routes['redirect']['params'], $this->routes['redirect']['options'], $this->routes['redirect']['reuseMatchedParams']);
        }

        $form = new SigninForm();
        $form->setAttribute('action', $this->url()->fromRoute($this->routes['authenticate']['name'], $this->routes['authenticate']['params'], $this->routes['authenticate']['options'], $this->routes['authenticate']['reuseMatchedParams']));

        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();
            $form->setData($post);
            if ($form->isValid()) {

                $authAdapter = $this->authenticationService->getAdapter();
                $authAdapter->setIdentityValue($form->get('username')->getValue());
                $authAdapter->setCredentialValue(sha1(sha1($form->get('password')->getValue())));

                $authResult = $this->authenticationService->authenticate();

                if ($authResult->isValid()) {
                    $identity = $authResult->getIdentity();

                    $authStorage = $this->authenticationService->getStorage();
                    if ($form->get('remember-me')->getValue() == 1) {
                        $authStorage->setRememberMe(1);
                    }
                    $authStorage->write($identity);

                    $this->flashMessenger()->addSuccessMessage(_('Sign in with success!'));

                    return $this->redirect()->toRoute($this->routes['redirect']['name'], $this->routes['redirect']['params'], $this->routes['redirect']['options'], $this->routes['redirect']['reuseMatchedParams']);
                } else {
                    $this->flashMessenger()->addErrorMessage(_('Username or password is invalid.'));
                }
            }
        }
        
        return $this->redirect()->toRoute($this->routes['signin']['name'], $this->routes['signin']['params'], $this->routes['signin']['options'], $this->routes['signin']['reuseMatchedParams']);
    }

    public function signoutAction()
    {
        if (!$this->identity()) {
            return $this->redirect()->toRoute($this->routes['signin']['name'], $this->routes['signin']['params'], $this->routes['signin']['options'], $this->routes['signin']['reuseMatchedParams']);
        }

        $this->authenticationService->getStorage()->forgetMe();
        $this->authenticationService->clearIdentity();
        $this->flashMessenger()->addErrorMessage(_('You\'re disconected!'));
        return $this->redirect()->toRoute($this->routes['redirect']['name'], $this->routes['redirect']['params'], $this->routes['redirect']['options'], $this->routes['redirect']['reuseMatchedParams']);
    }

    public function signupAction()
    {
        if ($this->identity()) {
            return $this->redirect()->toRoute($this->routes['redirect']['name'], $this->routes['redirect']['params'], $this->routes['redirect']['options'], $this->routes['redirect']['reuseMatchedParams']);
        }

        $form = new SignupForm($this->entityManager, 'signup', ['config' => $this->config]);
        $form->setAttribute('action', $this->url()->fromRoute($this->routes['signup']['name'], $this->routes['signup']['params'], $this->routes['signup']['options'], $this->routes['signup']['reuseMatchedParams']));
        $identityClass = $this->config['identityClass'];
        /** @var UserInterface $user */
        $user = new $identityClass();
        $form->bind($user);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $credentialClass = $this->config['credentialClass'];
                /** @var CredentialInterface $credential */
                $credential = new $credentialClass();
                $credential->setType($this->config['credentialType']);
                $credential->setValue(sha1(sha1($form->get('password')->getValue())));
                $credential->setUser($user);

                /** @var RoleInterface $role */
                $role = $this->entityManager->find($this->config['roleClass'], $this->config['roleDefault']);
                $user->setRole($role);

                $user->setAvatar($this->tssImageThumb()->getDefaultImageThumb());
                $user->setSignAllowed($this->config['signAllowed']);
                $user->setToken(sha1(uniqid(mt_rand(), true)));

                $this->entityManager->persist($user);
                $this->entityManager->persist($credential);
                $this->entityManager->flush();


                $fullLink = $this->url()->fromRoute(
                    $this->routes['confirm-email']['name'],
                    [
                        'token' => $user->getToken(),
                    ],
                    ['force_canonical' => true]
                );
                $to = $user->getEmail();
                $subject = $this->translator->translate('Please, confirm your registration!');
                $body = $this->translator->translate('Please, click the link to confirm your registration => ') . $fullLink;

                $this->tssEmail()->send($to, $subject, $body);

                $this->flashMessenger()->addSuccessMessage(sprintf($this->translator->translate('An email has been sent to %s. Please, check your inbox and confirm your registration!'), $user->getEmail()));

                return $this->redirect()->toRoute($this->routes['signin']['name'], $this->routes['signin']['params'], $this->routes['signin']['options'], $this->routes['signin']['reuseMatchedParams']);
            } else {
                $this->flashMessenger()->addErrorMessage(_('Form with errors!'));
            }
        }

        $form->prepare();
        $viewModel = new ViewModel([
            'form' => $form,
            'routes' => $this->routes
        ]);

        $viewModel->setTemplate($this->templates['signup']);
        $this->layout($this->layoutView);

        return $viewModel;
    }

    public function confirmEmailAction()
    {
        $identityRepo = $this->entityManager->getRepository($this->config['identityClass']);

        $token = $this->params()->fromRoute('token', 0);

        if ($this->identity()) {
            $this->authenticationService->getStorage()->forgetMe();
            $this->authenticationService->clearIdentity();
        }

        $qb = $identityRepo->createQueryBuilder('i');
        $qb->where('i.token = :token');
        $qb->setParameter('token', $token);
        /** @var UserInterface $identity */
        $identity = $qb->getQuery()->getOneOrNullResult();

        if ($identity == null) {
            $this->flashMessenger()->addErrorMessage(_('Token invalid or you already confirmed this link.'));
            return $this->redirect()->toRoute($this->routes['signin']['name'], $this->routes['signin']['params'], $this->routes['signin']['options'], $this->routes['signin']['reuseMatchedParams']);
        }

        if (!$identity->isConfirmedEmail()) {
            $identity->setToken(sha1(uniqid(mt_rand(), true))); // change immediately taken to prevent multiple requests to db
            $identity->setSignAllowed(true);
            $identity->setConfirmedEmail(true);
            $this->entityManager->flush();
            $this->authenticationService->getStorage()->write($identity);
            $this->flashMessenger()->addSuccessMessage(_('Email confirmed.'));
            return $this->redirect()->toRoute($this->routes['redirect']['name'], $this->routes['redirect']['params'], $this->routes['redirect']['options'], $this->routes['redirect']['reuseMatchedParams']);
        } else {
            $identity->setToken(sha1(uniqid(mt_rand(), true))); // change immediately taken to prevent multiple requests to db
            $this->entityManager->flush();
            $this->flashMessenger()->addInfoMessage(_('Email already verified. Please login!'));
            return $this->redirect()->toRoute($this->routes['signin']['name'], $this->routes['signin']['params'], $this->routes['signin']['options'], $this->routes['signin']['reuseMatchedParams']);
        }
    }

    public function recoverAction()
    {
        $identityRepo = $this->entityManager->getRepository($this->config['identityClass']);
        if ($this->identity()) {
            return $this->redirect()->toRoute($this->routes['redirect']['name'], $this->routes['redirect']['params'], $this->routes['redirect']['options'], $this->routes['redirect']['reuseMatchedParams']);
        }

        $form = new RecoverForm();
        $form->setAttribute('action', $this->url()->fromRoute($this->routes['recover']['name'], $this->routes['recover']['params'], $this->routes['recover']['options'], $this->routes['recover']['reuseMatchedParams']));

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $identity = $identityRepo->findOneBy([$this->config['identityEmail'] => $form->get('email')->getValue()]);
                if (is_null($identity)) {
                    $this->flashMessenger()->addErrorMessage('Email not found');
                } else {
                    $identity->setToken(sha1(uniqid(mt_rand(), true)));

                    $this->entityManager->flush();

                    $fullLink = $this->url()->fromRoute(
                        $this->routes['password-recover']['name'],
                        [
                            'token' => $identity->getToken(),
                        ],
                        ['force_canonical' => true]
                    );
                    $to = $identity->getEmail();
                    $subject = $this->translator->translate('Recover password!');
                    $body = $this->translator->translate('Please, click the link to recover your password => ') . $fullLink;

                    $this->tssEmail()->send($to, $subject, $body);

                    $this->flashMessenger()->addSuccessMessage(sprintf($this->translator->translate('An email has been sent to %s. Please, check your inbox and recover your password!'), $identity->getEmail()));

                    return $this->redirect()->toRoute($this->routes['signin']['name'], $this->routes['signin']['params'], $this->routes['signin']['options'], $this->routes['signin']['reuseMatchedParams']);
                }
            } else {
                $this->flashMessenger()->addErrorMessage(_('Form with errors!'));
            }
        }

        $form->prepare();
        $viewModel = new ViewModel([
            'form' => $form,
            'routes' => $this->routes
        ]);

        $viewModel->setTemplate($this->templates['recover']);
        $this->layout($this->layoutView);

        return $viewModel;
    }

    public function passwordRecoverAction()
    {
        $identityRepo = $this->entityManager->getRepository($this->config['identityClass']);
        $credentialRepo = $this->entityManager->getRepository($this->config['credentialClass']);

        $token = $this->params()->fromRoute('token', 0);

        if ($this->identity()) {
            $this->authenticationService->getStorage()->forgetMe();
            $this->authenticationService->clearIdentity();
        }

        $qb = $identityRepo->createQueryBuilder('i');
        $qb->where('i.token = :token');
        $qb->setParameter('token', $token);
        /** @var UserInterface $identity */
        $identity = $qb->getQuery()->getOneOrNullResult();

        if ($identity == null) {
            $this->flashMessenger()->addErrorMessage(_('Token invalid or you already confirmed this link.'));
            return $this->redirect()->toRoute($this->routes['signin']['name'], $this->routes['signin']['params'], $this->routes['signin']['options'], $this->routes['signin']['reuseMatchedParams']);
        }

        $form = new PasswordChangeForm();
        $this->routes['password-recover']['params']['token'] = $token;
        $form->setAttribute('action', $this->url()->fromRoute($this->routes['password-recover']['name'], $this->routes['password-recover']['params'], $this->routes['password-recover']['options'], $this->routes['password-recover']['reuseMatchedParams']));
        $form->getInputFilter()->get('password-old')->setRequired(false);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $data = $form->getData();
                $credential = $credentialRepo->findOneBy(array($this->config['credentialIdentityProperty'] => $identity, 'type' => $this->config['credentialType']));
                $passwordNew = sha1(sha1($data['password-new']));

                $identity->setToken(sha1(uniqid(mt_rand(), true)));
                $credential->setValue($passwordNew);

                $this->entityManager->flush();

                $this->flashMessenger()->addSuccessMessage(_('Your password has been changed successfully!'));

                return $this->redirect()->toRoute($this->routes['signin']['name'], $this->routes['signin']['params'], $this->routes['signin']['options'], $this->routes['signin']['reuseMatchedParams']);
            } else {
                $this->flashMessenger()->addErrorMessage(_('Form with errors!'));
            }
        }

        $form->prepare();
        $viewModel = new ViewModel([
            'form' => $form,
            'routes' => $this->routes
        ]);

        $viewModel->setTemplate($this->templates['password-recover']);
        $this->layout($this->layoutView);

        return $viewModel;
    }
}
