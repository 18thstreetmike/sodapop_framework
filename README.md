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

1. Code should be well-organized; **everything should be easily discoverable** if one knows the basics of the framework.
2. **Boilerplate code is unnecessary.** Let computers handle the repetitive stuff, they enjoy it more.
3. **Convention over configuration.** You shouldn't have to state everything explicitly, but you can override defaults.
4. A framework should **get out of the way** and not make you change your style.

It has several features that support these values.

- The [directory structure] [4] is simple and consistent, and the (default) naming conventions make it easy to find things.
- Any changes to the [routing] [5] system happen in a single file, routes.json, so they will be easy to manage.



[1]: http://sodapop.restlessdev.com/documentation/controllers   "Controllers"
[2]: http://sodapop.restlessdev.com/documentation/views    "Views"
[3]: http://sodapop.restlessdev.com/documentation/models    "Models"
[4]: http://sodapop.restlessdev.com/documentation/sodapop_directories   "Directory Structure"
[5]: http://sodapop.restlessdev.com/documentation/routes_conf   "Routing"