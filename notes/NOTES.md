The way the Dig serializes needs to be better abstracted and cleaner. Would also like to see no artifact handling going on inside the class. Something else should instantiate excavations and pass into the dig.

So what should the dig be aware of then?;
- The excavations going on in the dig
- Should contain a status object so we do $dig->status->restartNeeded()   
- To serialize we will just call the Serializer which will  

We need to accurately determine when an extension is already installed. And simply reject it. User should have to run

`forge update k2`       

update install uninsall are fallgged/stored on artifact. the create excavation handles creating the correct excavation class  

thats perfect because we might be passing in mutilpe instlaltions at once

`forge install k2 akeeba backrub ducky` etc.

Before these artfiacts get into the Digger they are massaged with extra flags     

# Tasks

We could allow an excavator to manually keep track of what task it is on. Statically provide a number of tasks. The update task on method. bingo. Whenever we do something that is taskable we move our position to startingTask() and then endedTask(). Can even provide messages

we can still break them up into tasks we just share the code between the classes and controller the order. task simply call the function. bingo. If wee need to we can simply duplicate cocde between classes.

Uninstallers are probably going to be the easy to split up into tasks. Tackle those first.    

Need to serialize entire current excavation on pause. Then load somehow on restart
We will seralize anything run through $this->set? Or maybe not cause what about DB instance?.     

Just store the packages name correctly in vendor/packages of joomla install. No ned to has test/data    

failedExcavation die should it be removed?