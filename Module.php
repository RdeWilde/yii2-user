<?php

namespace amnah\yii2\user;

use Yii;
use yii\db\ActiveRecord;
use yii\base\InvalidConfigException;

/**
 * User module
 *
 * @author amnah <amnah.dev@gmail.com>
 */
class Module extends \yii\base\Module {

    /**
     * @var string Alias for module
     */
    public $alias = "@user";

    /**
     * @var bool If true, users are required to enter an email
     */
    public $requireEmail = true;

    /**
     * @var bool If true, users are required to enter a username
     */
    public $requireUsername = false;

    /**
     * @var bool If true, users can enter an email. This is automatically set to true if $requireEmail = true
     */
    public $useEmail = true;

    /**
     * @var bool If true, users can enter a username. This is automatically set to true if $requireUsername = true
     */
    public $useUsername = true;

    /**
     * @var bool If true, users can log in using their email
     */
    public $loginEmail = true;

    /**
     * @var bool If true, users can log in using their username
     */
    public $loginUsername = true;

    /**
     * @var int Login duration
     */
    public $loginDuration = 2592000; // 1 month

    /**
     * @var array|string Url to redirect to after logging in
     */
    public $loginRedirect = ["/user"];

    /**
     * @var array|string Url to redirect to after logging out
     */
    public $logoutRedirect = ["/"];

    /**
     * @var bool If true, users will have to confirm their email address after registering (= email activation)
     */
    public $emailConfirmation = true;

    /**
     * @var bool If true, users will have to confirm their email address after changing it on the account page
     */
    public $emailChangeConfirmation = true;

    /**
     * @var string Time before userkeys expire (currently only used for password resets)
     */
    public $resetKeyExpiration = "48 hours";

    /**
     * @var string|null View path. Leave as null to use default "@user/views"
     */
    public $viewPath;

    /**
     * @var string Email view path
     */
    public $emailViewPath = "@user/mails";

    /**
     * @var array Model classes, e.g., ["User" => "amnah\yii2\user\models\User"]
     * Usage:
     *   $user = Yii::$app->getModule("user")->model("User", $config);
     *   (equivalent to)
     *   $user = new \amnah\yii2\user\models\User($config);
     *
     * The model classes here will be merged with/override the [[_getDefaultModelClasses()|default ones]]
     */
    public $modelClasses = [];

    /**
     * @var array Storage for models based on $modelClasses
     */
    protected $_models;

    /**
     * @inheritdoc
     */
    public function init() {

        parent::init();

        // check for valid email/username properties
        $this->_checkEmailUsername();

        // override modelClasses
        $this->modelClasses = array_merge($this->_getDefaultModelClasses(), $this->modelClasses);

        // set alias
        $this->setAliases([
            $this->alias => __DIR__,
        ]);
    }

    /**
     * Check for valid email/username properties
     */
    protected function _checkEmailUsername() {

        // set use fields based on required fields
        if ($this->requireEmail) {
            $this->useEmail = true;
        }
        if ($this->requireUsername) {
            $this->useUsername = true;
        }

        // get class name for error messages
        $className = get_called_class();

        // check required fields
        if (!$this->requireEmail and !$this->requireUsername) {
            throw new InvalidConfigException("{$className}: \$requireEmail and/or \$requireUsername must be true");
        }
        // check login fields
        if (!$this->loginEmail and !$this->loginUsername) {
            throw new InvalidConfigException("{$className}: \$loginEmail and/or \$loginUsername must be true");
        }
        // check email fields with emailConfirmation/emailChangeConfirmation is true
        if (!$this->useEmail and ($this->emailConfirmation or $this->emailChangeConfirmation)) {
            $msg = "{$className}: \$useEmail must be true if \$email(Change)Confirmation is true";
            throw new InvalidConfigException($msg);
        }
    }

    /**
     * Get default model classes
     */
    protected function _getDefaultModelClasses() {
        
        // use single quotes so nothing gets escaped
        return [
            'User' => 'amnah\yii2\user\models\User',
            'Profile' => 'amnah\yii2\user\models\Profile',
            'Role' => 'amnah\yii2\user\models\Role',
            'Userkey' => 'amnah\yii2\user\models\Userkey',
            'ForgotForm' => 'amnah\yii2\user\models\forms\ForgotForm',
            'LoginForm' => 'amnah\yii2\user\models\forms\LoginForm',
            'ResendForm' => 'amnah\yii2\user\models\forms\ResendForm',
            'ResetForm' => 'amnah\yii2\user\models\forms\ResetForm',
            'UserSearch' => 'amnah\yii2\user\models\search\UserSearch',
        ];
    }

    /**
     * Get object instance of model
     * @param string $name
     * @param array $config
     * @return ActiveRecord
     */
    public function model($name, $config = []) {

        // return object if already created
        if (!empty($this->_models[$name])) {
            return $this->_models[$name];
        }

        // create object
        $className = $this->modelClasses[ucfirst($name)];
        $obj = Yii::createObject(array_merge(["class" => $className], $config));

        // store and return
        $this->_models[$name] = $obj;
        return $obj;
    }

    /**
     * Modify createController() to handle routes in the default controller
     *
     * This is a temporary hack until they add in url management via modules
     * @link https://github.com/yiisoft/yii2/issues/810
     * @link http://www.yiiframework.com/forum/index.php/topic/21884-module-and-url-management/
     *
     * "user" and "user/default" work like normal
     * "user/xxx" gets changed to "user/default/xxx"
     *
     * @inheritdoc
     */
    public function createController($route) {

        // check valid routes
        $validRoutes = [$this->defaultRoute, "admin", "copy"];
        $isValidRoute = false;
        foreach ($validRoutes as $validRoute) {
            if (strpos($route, $validRoute) === 0) {
                $isValidRoute = true;
                break;
            }
        }

        return (empty($route) or $isValidRoute)
            ? parent::createController($route)
            : parent::createController("{$this->defaultRoute}/{$route}");
    }

    /**
     * Get a list of actions for this module. Used for debugging/initial installations
     */
    public function getActions() {

        return [
            "User" => "/{$this->id}",
            "Admin" => "/{$this->id}/admin",
            "Login" => "/{$this->id}/login",
            "Logout" => "/{$this->id}/logout",
            "Register" => "/{$this->id}/register",
            "Account" => "/{$this->id}/account",
            "Profile" => "/{$this->id}/profile",
            "Forgot password" => "/{$this->id}/forgot",
            "Reset" => [
                "url" => "/{$this->id}/reset?key=xxxxxxxxxx",
                "description" => "Reset password. Automatically generated with key from 'Forgot password' page",
            ],
            "Resend" => [
                "url" => "/{$this->id}/resend",
                "description" => "Resend email confirmation (for both activation and change of email)",
            ],
            "ResendChange" => [
                "url" => "/{$this->id}/resend-change",
                "description" => "Resend email change confirmation (quick link on the 'Account' page)",
            ],
            "Cancel" => [
                "url" => "/{$this->id}/cancel",
                "description" => "Cancel email change confirmation. <br/>This and ResendChange appear on the 'Account' page",
            ],

            "Confirm" => [
                "url" => "/{$this->id}/confirm?key=xxxxxxxxx",
                "description" => "Confirm email address. Automatically generated with key",
            ],
        ];
    }
}
