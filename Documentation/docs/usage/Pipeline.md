# Pipeline

A pipeline is a collection of steps to be executed in a precise order: it is
defined as an instance of the __Gishiki\Pipeline\Pipeline__ class.

With a pipeline you __MAY__ or __MAY NOT__ have the full control of the pipeline
execution.

Pipelines are great when you have to execute long operations outside the context
of the operation request, but are also great to control the result of those operation
in a different time from theirs execution.


## Pipeline Definition

To execute a pipeline you have to define it.

A pipeline __MUST__ have a name and a collection of zero or more steps.

Obviously a pipeline with 0 steps is totally useless!

A pipeline with the same name of an another pipeline __CANNOT__ exists: at the moment
of creation an exception will be thrown and the new pipeline will cease to exists!

Defining a pipeline is done like this:

```php
use Gishiki\Pipeline\Pipeline;
use Gishiki\Algorithms\Collections\SerializableCollection;

//create the pipeline
$pipeline = new Pipeline("EmailUser");

//add a step to the pipeline
$pipeline->bindStage('send', function (SerializableCollection &$collection) {
    //send the email (long task)
    return mail($collection->dest, $collection->obj, $collection->msg);
});
```

Now it is clear that $collection is an argument that will be passed when the
pipeline will be executed.

A pipeline should be defined in a file called as the pipeline inside the folder
called 'pipelines', which is inside the 'application' folder.


## Pipeline Runtime

In order for the pipeline execution to take place a runtime __MUST__ be created.

A pipeline runtime is an instance of the __Gishiki\Pipeline\PipelineRuntime__ class.

A runtime is created from a specific pipeline, have a type and a priority.

### Runtime Type

The type of the runtime identifies the timing of the execution, the type __MUST__ be
one of the following possible types:

   - Gishiki\Pipeline\RuntimeType::ASYNCHRONOUS 
   - Gishiki\Pipeline\RuntimeType::SYNCHRONOUS

A synchronous runtime is a runtime that has a known execution timing: it is certain
__WHEN__ the pipeline is executed, because the execution is triggered on demand.

An asynchronous runtime is executed with an unknown timing: you can only be sure that
sooner or later it __WILL FOR SURE__ be executed.

### Runtime Priority

The priority of a pipeline changes the execution timing of an ASYNCHRONOUS runtime:
it has no effects on a SYNCHRONOUS runtime.

The higher the runtime priority the lower the time to wait before the execution
of the ASYNCHRONOUS runtime!


## Pipeline Execution

To create a runtime (described above in the "Pipeline Runtime" section) an instance
of the Pipeline to be executed is needed.

You can retrieve it knowing its name like this:

```php
use Gishiki\Pipeline\Pipeline;
use Gishiki\Pipeline\PipelineCollector;
use Gishiki\Pipeline\PipelineRuntime;
use Gishiki\Pipeline\PipelineRuntime;
use Gishiki\Algorithms\Collections\SerializableCollection;

//retrieve the pipeline
$pipeline = PipelineCollector::getPipelineByName("EmailUser");

//create the runtime with given params
$pipelineExecutor = new PipelineRuntime($pipeline, RuntimeType::SYNCHRONOUS, RuntimePriority::LOWEST, [
    'dest'  => '',
    'obj'   => '',
    'msg'   => ''
]);

//execute ALL STAGES of the pipeline
$pipelineExecutor();
```

If (like in the previous example) you created a pipeline that needs input parameters
in order to work you can add those parameters as the fourth parameter of the PipelineRuntime constructor.

*Note:* the runtime is SYNCHRONOUS, so the priority is ignored, but you can change
the runtime type while executing the pipeline!

Using the runtime object as a function will cause the runtime to execute the first
x stages of the pipeline, if x is not given, as in the example then __ALL__ the pipeline
will be executed!


## Cronjob

You have seen how to execute synchronous runtimes, but what about asynchronous?

Well, Gishiki is providing a cronjob that executes a number of
asynchronous runtimes when called.

That cronjob is invoked by an HTTP request to /cronjob address.

In an production environment (see [conficuration](configuration.md) chapter) /cronjob
can be called by everyone, since it __NEVER EXPOSES__ important output!

You can configure the number of runtimes to complete by including (in the request
body) a json that once evaluated will produce a SerializableCollection that has
a key named 'runtimes' that has as value the number of runtimes to be executed. 