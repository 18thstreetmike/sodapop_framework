The Sodapop Framework
=====================

Sodapop is a simple PHP MVC framework. You can read more about it at http://sodapop.restlessdev.com.

Its syntax should be immediately familiar to anyone who has used Ruby on Rails, Zend Framework, or other
Model-View-Controller frameworks.

A Quick Example
---------------

A [Controller] [1]:
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

A [Layout] [2]:
```php
<html>
    <head>
        <title>Blog: <?= $this->post->post_title ?></title>
    </head>
    <body>
       <?= $this->viewContent ?>
    </body>
</html>
```

A [View] [2] Template:
```php
    <h2><?= $this->post->post_title ?></h2>
    <div class="post-body">
        <?= $this->post->post_body ?>
    </div>
```

A [Model] [3]:
In typical CRUD-type operations, Sodapop doesn't require model classes to be explicitly defined.
See http://sodapop.restlessdev.com/documentation/models for more details.

[1]: http://sodapop.restlessdev.com/documentation/controllers   "Controllers"
[2]: http://sodapop.restlessdev.com/documentation/views    "Views"
[3]: http://sodapop.restlessdev.com/documentation/models    "Models"

Why Use Sodapop?
----------------

You have many choices in frameworks; why should you consider this one?

Sodapop has several core values that guide its development.