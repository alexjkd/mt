var tip = {
  $: function(ele) {
    if (typeof ele == "object") return ele;
    else if (typeof ele == "string" || typeof ele == "number")
      return document.getElementById(ele.toString());
    return null;
  },
  mousePos: function(e) {
    var x, y;
    var e = e || window.event;
    return {
      x:
        e.clientX +
        document.body.scrollLeft +
        document.documentElement.scrollLeft,
      y:
        e.clientY + document.body.scrollTop + document.documentElement.scrollTop
    };
  },
  start: function(obj) {
    var self = this;
    var t = self.$("mjs:tip");
    var t0 = self.$("mjs:tip0");
    var t1 = self.$("mjs:tip1");
    var t2 = self.$("mjs:tip2");
    var t3 = self.$("mjs:tip3");
    var t4 = self.$("mjs:tip4");
    var t5 = self.$("mjs:tip5");
    var t6 = self.$("mjs:tip6");
    var str = obj.toString();
    console.log("str:" + str);
    if (str.indexOf("#0") > 0) {
      t = t0;
    } else if (str.indexOf("#1") > 0) {
      t = t1;
    } else if (str.indexOf("#2") > 0) {
      t = t2;
    } else if (str.indexOf("#3") > 0) {
      t = t3;
    } else if (str.indexOf("#4") > 0) {
      t = t4;
    } else if (str.indexOf("#5") > 0) {
      t = t5;
    } else if (str.indexOf("#6") > 0) {
      t = t6;
    }
    obj.onmousemove = function(e) {
      var mouse = self.mousePos(e);
      t.style.left = mouse.x + 10 + "px";
      t.style.top = mouse.y + 10 + "px";
      t.innerHTML = obj.getAttribute("tips");
      t.style.display = "";
    };
    obj.onmouseout = function() {
      t.style.display = "none";
    };
  }
};
