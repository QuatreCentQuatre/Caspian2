#Caspian 2

### What's new
---

##1. Bootstrap, General

In Caspian 2, Everything is handled by the Application class. When the Application class is ready, the app is executed (like before).

Now, Every class that extends the Caspian\Base class as the **app** variable preloaded in it. Bundles, Controllers and View are no different (They all extend Caspian\Base).

What's in the **app** class? Everything, essentially. The most useful points are the following

1. bundles
2. helpers
3. events
4. session
5. config / constants / environment
6. site_url / root_path / uri / domain
7. route / alternate route
8. locale
9. input
10. router (routing class)

---

##2. Routes

Routes have changed a little. Now routes do not have 2 separate configuration for controller and method. The **action** configuration is used like so:

    action: method@controller
    
Also, in the interest of clarifying the routes, let say we have this route:

    fr: /blogue/(:param)/(:param)/
    
That is not clear what both of these **param** mean. This is where patterns come into play.

    patterns:
        category: (:param)
        post: (:param)
        
    ...
    fr: /blogue/{category}/{post}

Also, to help with clarifying routes, the trailing **/** is no longer required. The routing engine supports a route being called in the following manners:

    defined as: 
        /blog
    
    accepted as:
        /blog
        /blog/
        /blog.html
        /blog.json

---

##3. New Database API

In Caspian 2, we have a brand new API for database manipulations. Also in Caspian 2, the newer classes for MongoDB are used (MongoClient) Here is how it works and useful methods. For this example, we are going to use a model called "User"

    // Create an index on a field (two ways)
    User::index('field', 1/-1);
    $user->index('field', 1/-1);
    
    // Create a compound index of many fields (two ways)
    User::compound(array('field' => 1/-1, 'field2' => 1/-1));
    $user->compound(array('field' => 1/-1', 'field2' => 1/-1));

    // Get a record by it's id (two ways)
    $record = $user->findById(...);
    $record = User::withId(...)
    
    // Get a record with a query
    $record = $user->where(array(...))->order('field ASC')->find();
    
    // Get all record that match query
    $records = $user->where(array(...))->order('field ASC)->findAll();

    // Get all and paginate (page 1, 20 results per page)
    $records = $user->where(array(...))->paginate(1, 20)->findAll();

    // Get a reference from a record (reference_field needs to be a valid reference)
    $user->getReference($obj->reference_field);
    
    // Create a reference when creating a record (create a reference to a user)
    $record->field_name = User::reference(..id..);

    // Add a record
    $user->field = 'value';
    $user->save();
    
    // Update a record based on it's id
    $user->field_to_update = 'new value';
    $user->_id             = $id;
    $user->save();
    
    // Update a record based on a specific field
    $user->field_to_update = 'new value';
    $user->fielda          = 1;
    $user->save('fielda');
    
    // Add a file to GridFS (private $isGridFS = true must be set in your model)
    $model->your_meta = '...';
    $model->whatever_you_want = '..';
    $model->addFile('/path/to/file', true, false);         // local file
    $model->addFile($binary_data, false, false);           // binary data
    $model->addFile('_FILES_uploaded_index', true, true);  // uploaded file
    
    // Get a file by a query
    $file = $model->where(array('my_meta' => 'hello'))->getFile();    
    
    // Get a file by it's id
    $file = $model->getFileById('...');
    
    // Destroy a/many file(s) by a query
    $model->where(array('meta' => '..'))->destroyFiles(true/false) // true = limit to 1 (default)
    
    // Destroy a file by it's id
    $model->destroyFileById('...');

In Caspian 2, we introduce **QuickCall**. QuickCall enables you to interact with the database
without the need of a model. You can make a call like this

    $temporary_model = new Caspian\Database\Collection('the_collection_you_want');
    ...manipulations...
 

##4. Views

In Caspian 2, there is 2 small changes to the view class. First, **$this->data** is no more, call **$this->the_key_you_want** directly. It's shorter. Second, When you want a special layout (ex: a bundle layout instead of an app layout) in Caspian 1, you had to call **Bundle::layout_name**. Now, you use the **@** symbol.

    $this->view->useLayout('layout@bundle');

In Caspian 1, we had $this->css, $this->js, etc. In Caspian 2, these methods have been appropriately been move to the **HTML** helper. We now can call them like this:

    $this->html->js(...);
    
**NOTE:** The HTML helper is the only helper accessible this way within the view. All other (include html itself) are available in the **$this->app->helpers** object.

##5. API

In Caspian 1, we had a bundle that was bringing API functionalities to Caspian. In Caspian 2, all of this is baked into the core. This time though, we have oauth2 authentication for it. Here is how you define api routes

    route_tag:
        uri:
            fr: /some-api-resource
            en: /some-api-resource    # Same as FR, because the API is locale independent
        action: index@api             # controller@method
        secure: no                    # use https?
        enforce: no                   # enforce locale rule
        cache: no                     # cache page? (Not used by API)
        ttl: 60                       # cache TTL (Not used by API)
        api: true                     # Inform Caspian this route is part of an API
        verbs: [GET, POST]            # API Verbs allowed. [*] = All

Out of the box, the API engine handles 'account creation', token creation, token verification and scope without the need of a single line of code. It also supports permission creation and validation. The validation part requires you to check them in each of your calls. Here is an example of a permission verification call:

    $this->app->api->validatePermission('required_scope_permission');
    
To create an 'account' (essentially giving access to the api) you can call the service like this:

    $informations = $this->app->api->createAccess(array('list', 'of', 'permissions'));
    // $informations->key, $informations->secret
 
This method will create an API Key and Secret for you and store it in mongo automatically. 

<br/>
### What's gone?
---

1. Removed support for multiple apps
2. Removed MySQL support
3. Removed Profiler
4. Removed Automatic database reference loading