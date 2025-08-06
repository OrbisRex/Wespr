/*
 * JavaScripts for WESPR
 * @author: David Ehrlich
 * @licence: MIT
 */
var message;
var element;

var Nette = Nette || {};

//Get element by Data Attribute
function getAllElementsWithAttribute(attribute)
{
  var matchingElements = [];
  var allElements = document.getElementsByTagName('*');
  
  for (var i = 0; i < allElements.length; i++)
  {
    if (allElements[i].hasAttribute(attribute))
    {
      // Element exists with attribute. Add to array.
      matchingElements.push(allElements[i]);
    }
  }
  return matchingElements;
}

function getNumberChecked(dataAttribute) {
    var countChecked = 0;
    var selectedElement = getAllElementsWithAttribute(dataAttribute);
    
    for(var i = 0; i < selectedElement.length; i++) {
        if(selectedElement[i].type === 'checkbox' && selectedElement[i].checked) {
            ++countChecked; 
        };
        
        if(selectedElement[i].type === 'text' && selectedElement[i].value !== '') {
            ++countChecked; 
        };
    }
    return countChecked;
}

function warning(element){
    message = element.title;
    return decision = confirm(message);
};

function flashHide(element){
    var style = document.createAttribute('style');
    style.value = element.getAttribute('data-flash-hide');
    element.attributes.setNamedItem(style);
}

function elementEnable(slaver, slave) {
    var slaveElement = getAllElementsWithAttribute(slave);
    var slaverElement = getNumberChecked(slaver);
    
    if(slaverElement >= 1) {
        for(var i = 0; i < slaveElement.length; i++) {
            slaveElement[i].attributes.removeNamedItem('style');
            slaveElement[i].disabled = false;
        }
    } else if(slaverElement === 0) {
        for(var i = 0; i < slaveElement.length; i++) {
            var style = document.createAttribute('style');
            style.value = slaveElement[i].getAttribute(slave);
            slaveElement[i].attributes.setNamedItem(style);
            slaveElement[i].disabled = true;
        }
    }
};

function checkAll(slaver, slave) {
    var slaveElement = getAllElementsWithAttribute(slave);
    
    for(var i = 0; i < slaveElement.length; i++) {

        if(slaveElement[i].checked === false) {
            slaveElement[i].checked = true;
        } else {
            slaveElement[i].checked = false;
        }
    }
    
    elementEnable(slave, slaver);
}

function popFancyboxWin (element) {
    var popWin = document.getElementById(element);
    alert(popWin);
    popWin.display = true;
}

Nette.validators.verifyPassword = function (elem, arg, val) {
    if(arg === val) { 
        return true; 
    } else { 
        return false;
    }
};

function position (element) {
    var documentPosition = document.body.getBoundingClientRect();
    var hotPosition = element.getAttribute('data-yistudio-position-hot');
    
    if(documentPosition.top <= hotPosition) {
        element.style = element.getAttribute('data-yistudio-position-style');
    } else if(documentPosition.top > hotPosition && element.attributes.style) {
        element.attributes.removeNamedItem('style');
    }
};

window.onscroll = function () {
    var menu = document.getElementById('menu_container');
    position(menu);
};

/* Initializing .nette.ajax */
$(function () {
    $.nette.init();
});