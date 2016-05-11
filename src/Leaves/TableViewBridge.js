var table = function (leafPath) {
    window.rhubarb.viewBridgeClasses.ViewBridge.apply(this, arguments);
};

table.prototype = new window.rhubarb.viewBridgeClasses.ViewBridge();
table.prototype.constructor = table;

table.prototype.attachEvents = function () {
    var self = this;

    var nodes = this.viewNode.querySelectorAll('thead th.sortable');

    for(var i = 0; i < nodes.length; i++){
        nodes[i].addEventListener('click', function () {
            var ths = self.viewNode.querySelectorAll('thead th');
            var index = [].indexOf.call(ths,this);

            self.raiseServerEvent('columnClicked', index);
            return false;
        });
    }

    nodes = this.viewNode.querySelectorAll('tbody tr td.clickable');

    for(i = 0; i < nodes.length; i++) {
        nodes[i].addEventListener('click', function () {
            var tr = self.viewNode.parentNode;

            self.raiseClientEvent('rowClicked', tr);
            return false;
        });
    }
};

window.rhubarb.viewBridgeClasses.TableViewBridge = table;