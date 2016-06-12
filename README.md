# php-mini-router
##a simple and powerful php router,just have fun!

###Useage

  <?php
  $route = new Route(array(
      'errorHandle'=>function(){
          header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
      };
  ));
  
  $route->get('/','handle');
  $route->get('/user/{id:\d+}','handle');
  $route->get('/user/{name}','handle');
  $route->get('/user/{name:.+}','handle');
  $route->get('/user[/{id:\d+}[/{name}]]','handle');//
  
  //suport method get,post,put,delete,options,head
