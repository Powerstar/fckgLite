
  

  function MT_DataEvent(browser) {
      this.browser=browser;       
      this.list = new Array();
      this.notifies = 0;
      this.list['kb'] = new Array(); 
      this.list['events'] = new Array();     // this will enable any potential testing for other events
  }

  MT_DataEvent.prototype.push = function ( target, category, type ) {
        var list = (category == 'KeyboardEvent') ? this.list['kb'] : this.list['events'];        
        list.push({target:target, category: category, type:type });   
  };
  
  MT_DataEvent.eventOK = false; 
  MT_DataEvent.prototype.dispatch = function (data) {
     if(MT_DataEvent.eventOK) return;
     browser = this.browser;
     setTimeout(function () { MT_testEvent(data.target,browser, data.category, data.type); }, 250);
 }

  MT_DataEvent.prototype.eventLoop = function () {

       for(var i=0; i < this.list['kb'].length; i++) {
          if(i == 0) {
                var data = this.list['kb'][i];
                MT_testEvent(data.target,this.browser, data.category, data.type);
          }
          else this.dispatch(this.list['kb'][i]); 
      }
       if(MT_DataEvent.eventOK) return;
       for(var i=0; i < this.list['events'].length; i++) {
          if(i == 0) {
                var data = this.list['events'][i];
                MT_testEvent(data.target,this.browser, data.category, data.type);
          }

          else this.dispatch(this.list['events'][i]); 
      }

   
  }

    
/**  Categories:  "Events", "KeyboardEvent"
     type: keypress, etc
*/
   function MT_testEvent(target,browser, event_category, type) {
     if(MT_DataEvent.eventOK){ 
            return;
     }

      MT_DataEvent.receive(target, MT_DataEventTestHandler, type);
      MT_DataEvent.send(target, browser, event_category, type)
   }


   // This function removes the test handler and adds the permanent handlers if test is OK
    function MT_DataEventTestHandler(e) {  

      if(MT_DataEvent.eventOK) return;    // just a precaution
      MT_DataEvent.eventOK = true;
      if(document.detachEvent) {
        var type = "on" + e.type;
        e.targetObj.detachEvent(type, MT_DataEventTestHandler);  
       // e.targetObj.attachEvent("onmouseup", locktimer_count);
        e.targetObj.attachEvent("onkeypress", locktimer_count);         
      }
     else if(document.removeEventListener) {
          e.targetObj.removeEventListener(e.type, MT_DataEventTestHandler, false);  
        //  e.targetObj.addEventListener("mouseup", locktimer_count, false);
          e.targetObj.addEventListener("keypress", locktimer_count, false); 
     }
      
    }

 

    MT_DataEvent.send = function(target, data, event_category, type) {
        if (typeof target == "string") target = document.getElementById(target);

        // Create an event object.  If we can't create one, return silently

        if (document.createEvent) {                   //  DOM
            var e = document.createEvent(event_category);
            if(event_category == "Events") {            
              e.initEvent("dataavailable", true, false); 
            }
            else if(e.initKeyEvent){
                e.initKeyEvent(                                                                                      
                     "keypress",        //  in DOMString typeArg,                                                           
                      true,             //  in boolean canBubbleArg,                                                        
                      true,             //  in boolean cancelableArg,                                                       
                      null,             //  in nsIDOMAbstractView viewArg,  Specifies UIEvent.view. This value may be null.     
                      false,            //  in boolean ctrlKeyArg,                                                               
                      false,            //  in boolean altKeyArg,                                                        
                      false,            //  in boolean shiftKeyArg,                                                      
                      false,            //  in boolean metaKeyArg,                                                       
                       9,               //  in unsigned long keyCodeArg,                                                      
                       0);              //  in unsigned long charCodeArg);       
                }      
           else if(e.initEvent) {          
              e.initEvent("dataavailable", true, false); 
          }
          else return;   
        }
        else if (document.createEventObject) { // IE event model
            // In the IE event model, we just call this simple method
            var e = document.createEventObject();
        }
        else {
          return;  // Do nothing in other browsers
        }
        
        e.event_category = event_category;
        e.browser = data;
        e.targetObj = target;

        // Dispatch the event to the specified target
        if (target.dispatchEvent) target.dispatchEvent(e); // DOM 
        else if (target.fireEvent)  target.fireEvent(type, e);  
       

    };


    /**
     * Register an event handler for an ondataavailable event on the specified 
     * target element.
     */
    MT_DataEvent.receive = function(target, handler, type) {
        if (typeof target == "string") target = document.getElementById(target);
        if (target.addEventListener) {
            target.addEventListener(type, handler, false);         
        }
        else if (target.attachEvent)      
              target.attachEvent(type, handler);
    };


  MT_DataEvent.notify = function(data_evt) {
     data_evt.notifies++;     
     if(data_evt.notifies > 4) {
              top.resetDokuWikiLockTimer(); 
              return;
     }
     if(MT_DataEvent.eventOK) return;
     setTimeout(function () { MT_DataEvent.notify(data_evt); }, 350)
   
  }

    function MT_showElements(obj) {
        for(var e in obj) {     
          var msg = e + "=" + obj[e];
          if(msg.match(/(innerHtml|function|text)/i)) continue;
          if(!confirm(msg)) break;
        }
    }

