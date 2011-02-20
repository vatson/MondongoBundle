MondongoBundle
--------------

Bundle to use Mondongo with Symfony2.

Features
--------

  * Work with the last Symfony2 release
  * Generation of classes
  * Lazy load of the Mondongo when something need it
  * Load of data fixtures
  * Integration with the Symfony2 Profiler

Installation
------------

Add Mondongo to your vendors:

    git submodule add git://github.com/mondongo/mondongo vendor/mondongo

Add the MondongoBundle:

    git submodule add git://github.com/mondongo/MondongoBundle src/Mondongo/MondongoBundle

Add Mondongo and the MondongoBundle to your autoload:

    // app/autoload.php
    $loader->registerNamespaces(array(
        // ...
        'Mondongo'                 => __DIR__.'/../vendor/mondongo/src',
        'Mondongo\\MondongoBundle' => __DIR__.'/../src',
        // ...
    ));

Add the MondongoBundle to your application kernel:

    // app/AppKernel.php
    public function registerBundles()
    {
        return array(
            // ...
            new Mondongo\MondongoBundle\MondongoBundle(),
            // ...
        );
    }

Add mondongo to your configuration:

    # app/config/config.yml
    mondongo:
        connections:
            local:
                default: true
                server:   mongodb://localhost:27017
                database: symfony2_local
            global:
                server:   mongodb://localhost:27017
                database: symfony2_local

Activate the profiler in the developing environment:

    # app/config/config_dev.yml
    mondongo:
        log: true

Generating classes
------------------

The config classes are defined in the application and bundles config directories,
and they are defined in YAML.

    app/config/mondongo/*.yml
    *Bundle/Resources/config/mondongo/*.yml

You must to use the standard namespace *Model* in the classes, thus the bundles will be
reusable and coherent.

You don't need to use bundle namespace in your application classes:

    # app/config/mondongo/schema.yml
    Model\Article:
        fields:
            title:   string
            content: string

In the bundles is necessary to use the bundle name to separate the classes by bundle:

    # src/Mondongo/MondongoUserBundle/Resources/config/mondongo/schema.yml
    Model\MondongoUserBundle\User:
        fields:
            username: string
            password: string

The classes are generated in the *src/Model* directory, and the bundle classes extend of
a bundle class before of the base class to be able to custom them in the bundles.

    // application class
    Model\Article > Model\Base\Article

    // bundle class
    Model\MondongoUserBundle\User > Mondongo\MondongoUserBundle\Model\User > Model\MondongoUserBundle\Base\User

And to generate the classes is used the *mondongo:generate* command:

    php app/console mondongo:generate

Use
---

You can use Mondongo in a normal way.

You can access to the Mondongo in the container:

    $mondongo = $container->get('mondongo');

The Mondngo is initialized also automatically (in a lazy way) if you use some functionality in the
documents that require it:

    // creating
    $article = new \Model\Article();
    $article->setTitle($title);
    $article->save();

    // quering
    $articles = \Model\Article::query();

    // ...

Fixtures
--------

You can load fixtures defining them in YAML, in the application or in the bundles.

    app/fixtures/mondongo/*.yml
    *Bundle/Resources/fixtures/mondongo/*.yml

You can define everithing, including embeddeds documents and references:

    Model\Article:
        mondongo_rocks:
            title: Mondongo Rocks!
            author: pablodip                # reference_one
            categories: [php, mongodb]      # reference many
            source:
                url: http://mondongo.es     # embedded one
            comments:                       # embedded many
                -
                    author:  Pablo
                    comment: Yep

    Model\Author:
        pablodip:
            name: Pablo Díez

    Model\Category:
        php:
            name: PHP
        mongodb:
            name: MongoDB

And to load them you have to use the *mondongo:fixtures* command:

    php app/console mondongo:fixtures
