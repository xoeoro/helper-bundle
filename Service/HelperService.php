<?php

namespace Xoeoro\HelperBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Form\Form;
use Doctrine\Common\Collections\Collection;
use ArrayAccess;
use Symfony\Component\Validator\Exception\BadMethodCallException;

/**
 * Xoeoro Helper Service
 *
 * @author xoeoro <xoeoro@gmail.com>
 */
class HelperService
{
	/**
	 * @var EngineInterface
	 */
	protected $templating;

	/**
     * @var Request
     */
    protected $request;

    /**
	 * @var SecurityContextInterface
	 */
	protected $security_context;

	/**
	 * @var RouterInterface
	 */
	protected $router;

	/**
     * @var ParameterBagInterface
     */
    protected $parameterBag;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function setTemplating(EngineInterface $templating)
    {
    	$this->setTemplating($templating);
    }

    public function setRequest(Request $request)
    {
    	$this->request = $request;
    }

    public function setSecurityContext(SecurityContextInterface $security_context)
    {
    	$this->security_context = $security_context;
    }

    public function setRouter(RouterInterface $router)
    {
    	$this->router = $router;
    }

    public function setParameterBag(ParameterBagInterface $parameterBag = null)
    {
    	$this->parameterBag = $parameterBag ?: new ParameterBag();
    }

    public function setSession(SessionInterface $session)
    {
    	$this->session = $session;
    }

    public function setTraslator(TranslatorInterface $translator)
    {
    	$this->translator = $translator;
    }

    /**
	 * Register bundle in Kernel
	 * @param  KernelInterface $kernel
	 * @param  sting           $namespace
	 * @param  sting           $bundle
	 * @return boolean
     * @throws RuntimeException         When bundle already defined in <comment>AppKernel::registerBundles()</comment>
	 */
	public function registerBundle(KernelInterface $kernel, $namespace, $bundle)
    {
        $manip = new KernelManipulator($kernel);

        return $manip->addBundle($namespace.'\\'.$bundle);
    }

    public function redirect($url, $message = null)
    {
        if ($this->request->isXmlHttpRequest()) {
            $json = array();
            $json['redirect'] = $url;
            if ($message) {
                $json['message'] = $message;
            }

            return new JsonResponse($json);
        } else {
            if ($message) {
                $this->session->getFlashBag()->add('success', $message);
            }

            return new RedirectResponse($url);
        }
    }

    public function notFound($message = 'NotFound')
    {
        if ($this->request->isXmlHttpRequest()) {
            return new JsonResponse(array('message' => $message, 'code' => 404));
        } else {
            throw new NotFoundHttpException($message);
        }
    }

    public function accessDenied($message = 'AccessDenied')
    {
        if ($this->request->isXmlHttpRequest()) {
            return new JsonResponse(array('message' => $message, 'code' => 403));
        } else {
            throw new AccessDeniedException($message);
        }
    }

    public function getUser()
    {
        return is_object($this->security_context->getToken()) && is_object($this->security_context->getToken()->getUser()) ? $this->security_context->getToken()->getUser() : null;
    }

    public function getCurrentRoute()
    {
        try {
            return $this->router->match($this->router->getPathInfo());
        } catch (\Exception $e) {
            return array(
                '_route' => null,
                '_locale' => $this->getLocale(),
            );
        }
    }

    public function isCurrentRoute($route, $route_parameters = array())
    {
        $current_route = $this->getCurrentRoute();

        if ($route == $current_route['_route']) {
            foreach ($route_parameters as $k => $v) {
                if (!isset($current_route[$k]) || !$current_route[$k] == $v) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Gets a parameter.
     *
     * @param string $name    The parameter name
     * @param string $default The default value
     *
     * @return mixed The parameter value
     */
    public function getParameter($name = null, $default = null)
    {
        return $this->parameterBag->has($name) ? $this->parameterBag->get($name) : $default;
    }

    /**
     * Returns a rendered view.
     *
     * @param string $view       The view name
     * @param array  $parameters An array of parameters to pass to the view
     *
     * @return string The rendered view
     */
    public function renderView($view, array $parameters = array())
    {
        return $this->templating->render($view, $parameters);
    }

    /**
     * Renders a view.
     *
     * @param string   $view       The view name
     * @param array    $parameters An array of parameters to pass to the view
     * @param Response $response   A response instance
     *
     * @return Response A Response instance
     */
    public function render($view, array $parameters = array(), Response $response = null)
    {
        return $this->templating->renderResponse($view, $parameters, $response);
    }

    /**
     * Generate random string
     * @param  integer $length
     * @return string
     */
    public static function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public static function _unset()
    {
        $args = func_get_args();
        if (count($args) == 0) {
            return;
        }

        $object = $args[0];
        if (is_object($object)) {
            $clone = clone $object;
        } else {
            $clone = $object;
        }

        for ($k = 1; $k < count($args); $k++) {
            if (is_array($args[$k])) {
                foreach ($args[$k] as $v) {
                    if (is_object($clone)) {
                        unset($clone->{$v});
                    } else {
                        unset($clone[$v]);
                    }
                }
            } else {
                if (is_object($clone)) {
                    unset($clone->{$args[$k]});
                } else {
                    unset($clone[$args[$k]]);
                }
            }
        }

        return $clone;
    }

    public function getFormErrors(Form $form)
    {
        $errors = $form->getErrors();

        $_errors = array();
        foreach ($form->all() as $child) {
            foreach ($child->getErrors() as $key => $error) {
                $template = $error->getMessageTemplate();
                $parameters = $error->getMessageParameters();

                foreach ($parameters as $var => $value) {
                    $template = str_replace($var, $value, $template);
                }

                $template = $this->translator->trans($template, $parameters, 'validators');

                $_errors[$child->getName()][] = $template;
            }
        }

        return $_errors;
    }

    /**
     * Функция Делает из обычного массива массив по заданному ключу
     * Полезнен для результатов запроса к БД
     * Работает с объектами и массивами
     * $input - массив из массивов-элементов или массив из объектов-элементов
     * $key - название поля элементов, по которому будет стоится массив
     * $list - если false, то резльтипующий массив будет содержать мыссивы
     * если название поля, то результат - массив ключ=>значение
     */
    public static function array_list($input, $key, $list = false)
    {
        if (is_object($input) && $input instanceof Collection) {
            $input = $input->toArray();
        }

        if (!count($input)) {
            return $input;
        }

        $is_array = is_array(reset($input));

        $output = array();
        foreach ($input as $item) {
            if ($list) {
                $value = $is_array ? $item[$list] : self::getAttribute($item, $list);
            } else {
                $value = $item;
            }

            $key_val = $is_array ? $item[$key] : self::getAttribute($item, $key);

            $output[$key_val] = $value;
        }

        return $output;
    }

    /**
     * group array|object by key
     * @param  array|Collection $input
     * @param  string           $key
     * @return array            grouped array
     */
    public function groupBy($input, $key)
    {
        if (is_object($input)) {
            if ($input instanceof Collection) {
                $input = $input->toArray();
            } else {
                return $input;
            }
        }

        if (!count($input)) {
            return $input;
        }

        $is_array = is_array(reset($input));

        $output = array();
        foreach ($input as $item) {
            $key_val = $is_array ? $item[$key] : (property_exists(get_class($item), $key) ? $item->{$key} : call_user_func(array($item, $key)));
            $output[$key_val][] = $item;
        }

        return $output;
    }

    /**
     * Returns the attribute value for a given array/object.
     *
     * @param mixed  $object            The object or array from where to get the item
     * @param mixed  $item              The item to get from the array or object
     * @param array  $arguments         An array of arguments to pass if the item is an object method
     *
     * @return mixed The attribute value
     *
     * @throws BadMethodCallException
     */
    public static function getAttribute($object, $item, array $arguments = array())
    {
        if ((is_array($object) && array_key_exists($arrayItem, $object))
                || ($object instanceof ArrayAccess && isset($object[$arrayItem]))
            ) {
            return $object[$arrayItem];
        }elseif (!is_object($object)) {
            return false;
        }

        $class = get_class($object);

        // object method
        if (!isset(self::$cache[$class]['methods'])) {
            self::$cache[$class]['methods'] = array_change_key_case(array_flip(get_class_methods($object)));
        }

        $call = false;
        $lcItem = strtolower($item);
        if (isset(self::$cache[$class]['methods'][$lcItem])) {
            $method = (string) $item;
        } elseif (isset(self::$cache[$class]['methods']['get'.$lcItem])) {
            $method = 'get'.$item;
        } elseif (isset(self::$cache[$class]['methods']['is'.$lcItem])) {
            $method = 'is'.$item;
        } elseif (isset(self::$cache[$class]['methods']['__call'])) {
            $method = (string) $item;
            $call = true;
        } else {
            return false;
        }

        // Some objects throw exceptions when they have __call, and the method we try
        // to call is not supported. If ignoreStrictCheck is true, we should return null.
        try {
            return call_user_func_array(array($object, $method), $arguments);
        } catch (BadMethodCallException $e) {
            if ($call) {
                return;
            }
            throw $e;
        }
    }

    public static function crop($str, $length = 100, $strip_tags = true, $suffix = '...', $full_words = false)
    {
        if ($strip_tags) {
            $str = strip_tags($str);
        }

        $bak_str = $str;

        if (mb_strlen($str, 'UTF-8') > $length) {
            $str = mb_substr($str, 0, $length, 'UTF-8').$suffix;
        }

        if ($full_words) {
            $pattern = '/[\s,\.;:-]/';
            if (!(preg_match($pattern, substr($str, -1)) || preg_match($pattern, substr($bak_str, $length, 1)))) {
                preg_match_all($pattern, $str, $matches, PREG_OFFSET_CAPTURE);
                if (!empty($matches)) {
                    $str = substr($str, 0, self::last($matches[0])[1]);
                }
            }
        }

        return $str;
    }

    public static function first(&$array)
    {
        if (!is_array($array)) {
            return $array;
        }
        if (!count($array)) {
            return;
        }
        reset($array);

        return $array[key($array)];
    }

    public static function last(&$array)
    {
        if (!is_array($array)) {
            return $array;
        }
        if (!count($array)) {
            return;
        }
        end($array);

        return $array[key($array)];
    }

    public static function generateKeywords($str, $implode = false, $min_length = 2)
    {
        $pattern = '/[\s,\.;:-]/';
        $keywords = preg_split($pattern, $str);

        foreach ($keywords as $k => $v) {
            if (mb_strlen($v, 'UTF-8') < $min_length) {
                unset($keywords[$k]);
            }
        }

        if ($implode) {
            return implode($implode !== true ? $implode : ',', $keywords);
        } else {
            return $keywords;
        }
    }

    public static function getPlaceholdIt()
    {
        $args = func_get_args();
        $width = 0;
        $height = 0;
        $text = null;

        $i = 0;

        if (isset($args[$i])) {
            if (!is_numeric($args[$i])) {
                $tmp = explode('x', $args[$i]);
                if (count($tmp) == 2) {
                    $width = $tmp[0];
                    $height = $tmp[1];
                }
            } else {
                $width = $args[$i];
            }
        } else {
            return false;
        }

        $i++;

        if (!$height) {
            if (isset($args[$i]) && is_numeric($args[$i])) {
                $height = $args[$i];
                $i++;
            } else {
                $height = $width;
            }
        }

        if (!$text && isset($args[$i])) {
            $text = $args[$i];
        }

        return sprintf('http://placehold.it/%sx%s%s', $width, $height, $text ? sprintf('&text=%s', $text) : null);
    }

    protected function getPlaceholdItFile()
    {
        $placeholder = call_user_func_array(array($this, 'getPlaceholdIt'), func_get_args());
        $filename = sprintf('placeholder_%s', str_replace('&text=', '_', basename($placeholder)));
        $file = file_get_contents($placeholder);
        file_put_contents(sprintf('%s/%s', $this->getParameter('xoeoro.placeholder.upload.path'), $filename), $file);

        return $filename;
    }

    public function getPlaceholdItFileProtected()
    {
        $filename = call_user_func_array(array($this, 'getPlaceholdItFile'), func_get_args());

        return sprintf('%s/%s', $this->getParameter('xoeoro.placeholder.upload.path'), $filename);
    }

    public function getPlaceholdItFilePublic()
    {
        $filename = call_user_func_array(array($this, 'getPlaceholdItFile'), func_get_args());

        return sprintf('%s/%s', $this->getParameter('xoeoro.placeholder.public.path'), $filename);
    }

    public static function generateTableAlias($str)
    {
        if (preg_match_all('/[A-Z]/', $str, $matches)) {
            return mb_strtolower(implode('', end($matches)), 'UTF-8');
        } elseif (preg_match_all('/[\s,-_\.]/', $str, $matches)) {
            return mb_strtolower(implode(array_map(function ($a) {
                return substr($a, 0, 1);
            }, preg_split('/[\s,-_\.]/', $str))), 'UTF-8');
        } else {
            return substr($str, 0, 1);
        }
    }
}
