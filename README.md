The Sodapop Framework
=====================

Sodapop is a simple PHP MVC framework. You can read more about it at http://sodapop.restlessdev.com.

Its syntax should be immediately familiar to anyone who has used Ruby on Rails, Zend Framework, or other
Model-View-Controller frameworks.

A Quick Example
---------------

A Controller:
```php
<?php
class PostController extends Sodapop_Controller {
    // this is the action definition
    public function actionView() {
	// instantiate the Post model
        $post = new Post($this->request->slug); // or $_REQUEST['slug']

        // assign it to the view
        $this->view->post = $post;
    }
}
```