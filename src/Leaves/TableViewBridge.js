var table = function (leafPath) {
    window.rhubarb.viewBridgeClasses.UrlStateViewBridge.apply(this, arguments);
};

table.prototype = new window.rhubarb.viewBridgeClasses.UrlStateViewBridge();
table.prototype.constructor = table;

table.prototype.attachEvents = function () {
    var self = this;

    var nodes = this.viewNode.querySelectorAll('thead th.sortable');

    for(var i = 0; i < nodes.length; i++){
        nodes[i].addEventListener('click', function () {
            var ths = self.viewNode.querySelectorAll('thead th');
            var index = [].indexOf.call(ths,this);

            self.raiseServerEvent('columnClicked', index);

            if (self.model.urlStateName) {
                // Force string comparison to ensure -0 is seen as different from 0
                if (self.getUrlStateParam() === '' + index) {
                    self.setUrlStateParam('-' + index);
                } else {
                    self.setUrlStateParam(index);
                }
            }

            return false;
        });
    }

    nodes = this.viewNode.querySelectorAll('tbody tr td.clickable');

    for(i = 0; i < nodes.length; i++) {
        nodes[i].addEventListener('click', function (event) {
            var tr = event.target.parentNode;

            self.raiseClientEvent('rowClicked', tr);
            return false;
        });
    }
};

window.rhubarb.viewBridgeClasses.TableViewBridge = table;
