<?php
/**
 * 底层路由
 * @method get(string $route, Callable $callback)
 * @method post(string $route, Callable $callback)
 * @method put(string $route, Callable $callback)
 * @method delete(string $route, Callable $callback)
 * @method options(string $route, Callable $callback)
 * @method head(string $route, Callable $callback)
 * @author mantou<dadademantou@gmail.com>
 */

class Route{
    
    /* 路由容器*/
    public static $routes       = [];
    
    /* 回调容器*/
    public static $callbacks    = [];
    
    /* 参数容器*/
    public static $arguments    = [];
    
    /* 方法容器*/
    public static $methods      = [];
    
    /* 路由设置*/
    private $options            = [];

    const VARIABLE_REGEX            = '\{\s* ([a-zA-Z_][a-zA-Z0-9_-]*) \s*(?:: \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*))?\}';
    const DEFAULT_DISPATCH_REGEX    = '[^/]+';
    
    /**
     * 路由设置
     * @param array $options
     */
    public function __construct(array $options = []){
        $this->options = $options;
    }
    

    /**
     * 静动态方法
     * @param unknown $method
     * @param unknown $params
     */
    public function __call($method, $params)
    {
        $routeArr   = $this->parse(dirname($_SERVER['PHP_SELF']).'/'.$params[0]);
        $route      = '';
        $arguments  = [];
        foreach ( $routeArr as $k=>$v ){
            $route.=( is_array( $v )?"({$v[1]})":$v );
            is_array( $v ) and array_push( $arguments , $v[0]);
        }
        array_push(self::$routes, $route);
        array_push(self::$methods, strtoupper($method));
        array_push(self::$callbacks, $params[1]);
        array_push(self::$arguments, $arguments);
        return $this;
    }

    /**
     * 分发路由
     */
    public function dispatch()
    {
        $uri            = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method         = $_SERVER['REQUEST_METHOD'];
        $found          = false;
        self::$routes   = str_replace('//', '/', self::$routes);
        /* 直接解析*/
        if(in_array($uri, self::$routes)){
            $pos = array_keys(self::$routes, $uri);
            foreach ( $pos as $route ){
                if( self::$methods[$route] == $method ){
                    $found = true;
                    $this->run($route);
                }
            }
        /* 检查规则路由*/
        }else{
            $pos = 0;
            foreach ( self::$routes as $route ){
                if( !preg_match('#^' . $route . '$#', $uri, $matched) ){
                    if( self::$methods[$pos] != $method ){
                        $found = true;
                        $this->run($pos, $matched);
                        break;
                    }
                }
                $pos++;
            }
        }
        /*路由错误处理*/
        !$found and $this->error();
    }
    
    /**
     * 异常抛出
     * @param unknown $error
     */
    private function error()
    {
        if(!isset( $this->options['errorHandle'] )){
            $this->options['errorHandle'] = function(){
                header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
            };
        }
        call_user_func($this->options['errorHandle']);
        exit('404');
    }

    /**
     * 路由回调
     * @param unknown $pos
     * @param array $matched
     */
    private function run($pos,$matched=[])
    {
        array_shift($matched);
        !empty(self::$arguments[$pos]) and $matched = array_combine(self::$arguments[$pos],$matched);
        if (!is_object(self::$callbacks[$pos])) {
            $parts = explode('/',self::$callbacks[$pos]);
            $last  = end($parts);
            $segments   = explode('@',$last);
            $controller = new $segments[0]();
            !method_exists($controller, $segments[1]) and $this->error();
            call_user_func_array(array($controller, $segments[1]), $matched);
        } else {
            call_user_func_array(self::$callbacks[$pos], $matched);
        }
        return;
    }

    /**
     * 路由解析
     * @param unknown $route
     */
    private function parse($route)
    {
        if (!preg_match_all(
            '~' . self::VARIABLE_REGEX . '~x', $route, $matches,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
            )) {
                return [$route];
        }
        $offset     = 0;
        $routeData  = [];
        foreach ($matches as $set) {
            if ($set[0][1] > $offset) {
                $routeData[] = substr($route, $offset, $set[0][1] - $offset);
            }
            $routeData[] = [
                $set[1][0],
                isset($set[2]) ? trim($set[2][0]) : self::DEFAULT_DISPATCH_REGEX
            ];
            $offset = $set[0][1] + strlen($set[0][0]);
        }
        if ($offset != strlen($route)) {
            $routeData[] = substr($route, $offset);
        }
        return $routeData;
    }
}