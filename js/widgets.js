/**
 * Created by Vital Fadeev <vital.fadeev@gmail.com>  on 25.10.14.
 */

var UI = function ($) {
    /**
     * Checkbox
     */
    var Checkbox = function(element) {
        this.element = element;
        this.init();
    };

    Checkbox.prototype.update = function () {
        var $element = $(this.element);
        var value = $element.find('input').val();

        if (value == '0') {
            $element.removeClass('on');
            $element.addClass('off');
        } else {
            $element.removeClass('off');
            $element.addClass('on');
        }
    };

    Checkbox.prototype.init = function () {
        var $element = $(this.element);

        var html = '';
        //html += '<div class="checkbox">';
        html += '<div class="title">On</div>';
        html += '<div class="inner">';
        html += '  <input type="hidden" name="checkbox" value="0">';
        html += '  <div class="groove"></div>';
        html += '  <div class="switch">&nbsp;</div>';
        html += '</div>';
        //html += '</div>';

        $element.html(html);

        var data = $element.data();
        var current_id = data.defaults;

        $element.find('input').val(current_id);

        this.update();

        var self = this;
        $element.click(function(event) {
            self.toggle.call(self);
        });
    };

    Checkbox.prototype.toggle = function () {
        var $element = $(this.element);
        var $input = $element.find('input');
        var old_value = $input.val();

        if (old_value == '1') {
            $input.val(0);
        } else {
            $input.val(1);
        }

        this.update();

        // make ajax
        this.ajax();
    };

    Checkbox.prototype.ajax = function () {
        var self = this;
        var $element = $(this.element);
        var $input = $element.find('input');
        var data = $element.data();
        var url = decodeURIComponent(data.url);

        $.post(url, {value:$input.val()}, function(data, status, xhr) {
                if (!data.ok) {
                    console.error('Can not set property');
                    $input.val(old_value);
                    self.update.call(self);
                }
            },
            'json');
    };

    function Plugin(option) {
        return this.each(function () {
            new Checkbox(this);
        })
    }

    var old = $.fn.Checkbox;

    $.fn.Checkbox             = Plugin;
    $.fn.Checkbox.Constructor = Checkbox;

    $.fn.Checkbox.noConflict = function () {
        $.fn.Checkbox = old;
        return this
    }

}(jQuery);

var UI = function ($) {
    /**
     * CheckboxOld
     */
    var CheckboxOld = function(element) {
        this.element = element;
        this.init();
    };

    CheckboxOld.prototype.update = function () {
        var $element = $(this.element);
        var $input = $element.find('input');
        var value = $input.val();

        if (value == '0') {
            $element.removeClass('on');
            $element.addClass('off');
            $input.attr('checked', false);
        } else {
            $element.removeClass('off');
            $element.addClass('on');
            $input.attr('checked', true);
        }
    };

    CheckboxOld.prototype.init = function () {
        var $element = $(this.element);

        var html = '<label><input type="checkbox" name="checkbox" value="0"> yes</label>';
        /*
        //html += '<div class="checkbox">';
        html += '<div class="title">On</div>';
        html += '<div class="inner">';
        html += '  <input type="hidden" name="checkbox" value="0">';
        html += '  <div class="groove"></div>';
        html += '  <div class="switch">&nbsp;</div>';
        html += '</div>';
        //html += '</div>';
        */

        $element.html(html);

        var $input = $element.find('input');
        var data = $element.data();
        var current_id = data.defaults;

        $input.val(current_id);

        this.update();

        var self = this;
        $input.change(function(event) {
            self.toggle.call(self);
            event.stopPropagation();
            return false;
        });
    };

    CheckboxOld.prototype.toggle = function () {
        var $element = $(this.element);
        var $input = $element.find('input');
        var old_value = $input.val();

        if (old_value == '1') {
            $input.val(0);
        } else {
            $input.val(1);
        }

        this.update();

        // make ajax
        this.ajax();
    };

    CheckboxOld.prototype.ajax = function () {
        var self = this;
        var $element = $(this.element);
        var $input = $element.find('input');
        var data = $element.data();
        var url = decodeURIComponent(data.url);

        $.post(url, {value:$input.val()}, function(data, status, xhr) {
                if (!data.ok) {
                    console.error('Can not set property');
                    $input.val(old_value);
                    self.update.call(self);
                }
            },
            'json');
    };

    function Plugin(option) {
        return this.each(function () {
            new CheckboxOld(this);
        })
    }

    var old = $.fn.CheckboxOld;

    $.fn.CheckboxOld             = Plugin;
    $.fn.CheckboxOld.Constructor = CheckboxOld;

    $.fn.CheckboxOld.noConflict = function () {
        $.fn.CheckboxOld = old;
        return this
    }

}(jQuery);

var UI = function ($) {
    /**
     * Switcher
     */
    var Switcher = function(element) {
        this.element = element;
        this.init();
    };

    Switcher.prototype.update = function () {
        var $element = $(this.element);
        var $input = $element.find('input');
        var data = $element.data();
        var current_id = $input.val();
        var current_text = data.values[current_id];

        $element.find('.text').html(current_text);
    };

    Switcher.prototype.init = function () {
        var self = this;
        var $element = $(this.element);
        var data = $element.data();

        var html = '';
        //html += '<div class="switcher">';
        // html += '<div class="title">Switcher</div>';
        html += '<div class="inner">';
        html += '  <input type="hidden" name="switcher" value="">';
        html += '  <div class="text">&nbsp;</div>';
        html += '</div>';
        //html += '</div>';

        $element.html(html);
        var $input = $element.find('input');
        $input.val(data.defaults);

        this.update();

        // on click - scroll
        $element.click(function(event) {
            self.toggle.call(self);
        });
    };

    Switcher.prototype.toggle = function () {
        var $element = $(this.element);
        var $input = $element.find('input');
        var new_id = this.get_next();
        var data = $element.data();
        var new_text = data.values[new_id];

        $input.val(new_id);

        // animate
        var $old_slide = $element.find('.text');
        var $new_slide = $('<div/>', {class: 'text'});
        $new_slide.css('top', $element.height());

        $element.find('.inner').append($new_slide);
        $new_slide.html(new_text);

        $old_slide.stop().animate({top: -$element.height()}, 400, 'swing', function() {
            $old_slide.remove();
        });
        $new_slide.stop().animate({top: 0}, 400, 'swing');

        // ajax
        this.ajax();
    };

    Switcher.prototype.get_next = function () {
        var $element = $(this.element);
        var current_id = $element.find('input').val();
        var break_on_next = false;
        var new_id = null;
        var data = $element.data();

        for(var v in data.values) {
            if (!data.values.hasOwnProperty(v)) continue;

            if (break_on_next) {
                new_id = v;
                break;
            }

            if (v == current_id) {
                break_on_next = true;
            }
        }

        if (new_id == null) {
            // get first
            for(var v in data.values) {
                if (!data.values.hasOwnProperty(v)) continue;
                new_id = v;
                break;
            }
        }

        return new_id;
    };

    Switcher.prototype.ajax = function () {
        var self = this;
        var $element = $(this.element);
        var $input = $element.find('input');
        var data = $element.data();
        var url = decodeURIComponent(data.url);

        $.post(url, {value:$input.val()}, function(data, status, xhr) {
                if (!data.ok) {
                    console.error('Can not set property');
                    $input.val(old_value);
                    self.update.call(self);
                }
            },
            'json');
    };

    function Plugin(option) {
        return this.each(function () {
            new Switcher(this);
        })
    }

    var old = $.fn.Switcher;

    $.fn.Switcher             = Plugin;
    $.fn.Switcher.Constructor = Switcher;

    $.fn.Switcher.noConflict = function () {
        $.fn.Switcher = old;
        return this
    }

}(jQuery);


var UI = function ($) {
    /**
     * Chain
     */
    var Chain = function(element) {
        this.element = element;
        this.init();
    };

    Chain.prototype.update = function () {
        var $element = $(this.element);
        var $inner = $element.find('.inner');
        var jsoned = $element.find('input').val();
        var data = $element.data();
        var value, text, e, index=0;

        // clear
        $element.find('.box').remove();

        // add each
        var parsed = $.parseJSON(jsoned);

        for(var key in parsed) {
            if (!parsed.hasOwnProperty(key)) continue;

            value = parsed[key];
            text = data.values[value];
            e = this.create_element(key, text, index);
            $inner.append(e);

            index++;
        }

        e = this.create_empty(index);
        $inner.append(e);
    };

    // create one element
    Chain.prototype.create_element = function (id, text, index) {
        var self = this;

        var new_element = $('<div>', {
            class: "box",
            text: text,
            'data-id': id,
            'data-index': index,
            click: function() {
                self.box_click.call(self, this);
            }
        });

        return new_element;
    };

    // create empty
    Chain.prototype.create_empty = function (index) {
        var self = this;

        var new_element = $('<div>', {
            "class": "box add",
            text: '+',
            'data-index': index,
            click: function() {
                self.box_click.call(self, this);
            }
        });

        return new_element;
    };

    Chain.prototype.init = function () {
        var $element = $(this.element);

        var html = '';
        //html += '<div class="chain">';
        html += '<div class="inner">';
        html += '  <input type="hidden" name="chain" value="">';
        html += '  <div class="box">&nbsp;</div>';
        html += '</div>';
        //html += '</div>';

        $element.html(html);

        var data = $element.data();
        var jsoned = $.toJSON(data.defaults);
        $element.find('input').val(jsoned);

        this.update();
    };

    Chain.prototype.popup = function (values, current, callback) {
        var self = this;
        var $element = $(this.element);
        var value;

        // popup container
        var $popup = $("<div>", {
            class: 'popup'
        });

        // popup items
        for(var key in values) {
            if (!values.hasOwnProperty(key)) continue;

            value = values[key];

            $item = $("<div>", {
                class: 'item',
                text: value,
                'data-key': key,
                click: callback
            });

            $popup.append($item);
        }

        // popup shaded background
        var $overlay = $("<div>", {
            class: 'overlay',
            click: function(event) {
                $(this).remove();
                $element.find('.popup').remove();
                self.update.call(self);
            }
        });

        $element.append($popup);
        $element.append($overlay);
    };

    Chain.prototype.box_click = function (box) {
        var $element = $(this.element);
        var data = $element.data();
        var $input = $element.find('input');
        var self = this;
        var old_value = $input.val();
        var value, $item;

        // related box
        var box_data = $(box).data();
        var current = box_data.id;
        var box_index = box_data.index;

        // popup
        this.popup(data.values, current, function(event) {
            var data = $(this).data();

            self.insert_val.call(self, box_index, data.key);
            $element.find('.popup').remove();
            $element.find('.overlay').remove();
            self.update.call(self);

            // ajax
            self.ajax();
        });

        // remove box
        this.remove_box(box);

        // remove box value
        this.remove_val(box_index);

        // ajax
        this.ajax();
    };

    Chain.prototype.remove_box = function (box) {
        var $element = $(this.element);
        var $box = $(box);
        var height = $box.outerHeight();
        var width = $box.outerWidth();

        $box.animate({
            top: height,
            //width: width * 2,
            opacity: 0
        });
    };

    Chain.prototype.remove_val = function (index) {
        var $element = $(this.element);
        var $input = $element.find('input');
        var jsoned = $input.val();

        var values = $.parseJSON(jsoned);

        // remove
        values.splice(index, 1);

        $input.val($.toJSON(values));
    };

    Chain.prototype.insert_val = function (index, value) {
        var $element = $(this.element);
        var $input = $element.find('input');
        var jsoned = $input.val();

        var values = $.parseJSON(jsoned);

        // insert
        values.splice(index, 0, value);

        $input.val($.toJSON(values));
    };

    Chain.prototype.val = function () {
        var $element = $(this.element);
        var data = $element.data();
        var $input = $element.find('input');

        // get value
        if (arguments.length == 0) {
            return $input.val();
        }

        // set value
        else if (arguments.length == 1) {
            var value = arguments[0];

            if (typeof value != 'string') {
                value = $.toJSON(value);
            }

            $element.find('input').val(value);
            this.update();

            return this;
        }

        // set value to element
        else if (arguments.length == 2) {
            var element_index = arguments[0];
            var value = arguments[1];

            if (typeof element_index == 'string') {
                element_index = parseInt(element_index);
            }

            var current_values = $element.find('input').val();
            current_values = $.parseJSON(current_values);

            current_values[element_index] = value;

            var new_values = $.toJSON(current_values);

            $element.find('input').val(new_values);

            this.update();

            return this;
        }

    };

    Chain.prototype.ajax = function () {
        var self = this;
        var $element = $(this.element);
        var $input = $element.find('input');
        var data = $element.data();
        var url = decodeURIComponent(data.url);

        $.post(url, {value:$input.val()}, function(data, status, xhr) {
                if (!data.ok) {
                    console.error('Can not set property');
                    $input.val(old_value); // FIXME save old value
                    self.update.call(self);
                }
            },
            'json');
    };

    function Plugin(option) {
        return this.each(function () {
            new Chain(this);
        })
    }

    var old = $.fn.Chain;

    $.fn.Chain             = Plugin;
    $.fn.Chain.Constructor = Chain;

    $.fn.Chain.noConflict = function () {
        $.fn.Chain = old;
        return this
    }

}(jQuery);

var UI = function ($) {
    /**
     * ChainOld
     */
    var ChainOld = function(element) {
        this.element = element;
        this.init();
    };

    ChainOld.prototype.update = function () {
        var $element = $(this.element);
        var $inner = $element.find('.inner');
        var jsoned = $element.find('input').val();
        var data = $element.data();
        var value, text, e, index=0;

        // clear
        $element.find('select').remove();

        // add each
        var parsed = $.parseJSON(jsoned);

        for(var key in parsed) {
            if (!parsed.hasOwnProperty(key)) continue;

            value = parsed[key];
            text = data.values[value];
            e = this.create_element(key, text, index, value);
            $inner.append(e);

            index++;
        }

        e = this.create_empty(index);
        $inner.append(e);
    };

    // create one element
    ChainOld.prototype.create_element = function (id, text, index, selected_value) {
        var self = this;
        var $element = $(this.element);
        var data = $element.data();
        var value, $option;

        var $new_element = $('<select>', {
            class: "",
            'data-id': id,
            'data-index': index,
            change: function() {
                self.changed.call(self, this);
            }
        });

        // empty
        $option = $('<option>', {
            class: "",
            text: '',
            value: ''
        });
        $new_element.append($option);

        // popup items
        for(var key in data.values) {
            if (!data.values.hasOwnProperty(key)) continue;

            value = data.values[key];

            $option = $('<option>', {
                class: "",
                text: value,
                value: key
            });

            if (key == selected_value) {
                $option.attr('selected', true);
            }

            $new_element.append($option);
        };

        return $new_element;
    };

    // create empty
    ChainOld.prototype.create_empty = function (index) {
        var self = this;
        var $element = $(this.element);
        var data = $element.data();
        var value, $option;

        var $new_element = $('<select>', {
            class: "",
            'data-index': index,
            change: function() {
                self.changed.call(self, this);
            }
        });

        // empty
        $option = $('<option>', {
            class: "",
            text: '',
            value: ''
        });
        $new_element.append($option);

        // popup items
        for(var key in data.values) {
            if (!data.values.hasOwnProperty(key)) continue;

            value = data.values[key];

            $option = $('<option>', {
                class: "",
                text: value,
                value: key
            });

            $new_element.append($option);
        };

        return $new_element;
    };

    ChainOld.prototype.init = function () {
        var $element = $(this.element);

        var html = '';
        //html += '<div class="chain">';
        html += '<div class="inner">';
        html += '  <input type="hidden" name="chain" value="">';
        html += '</div>';
        //html += '</div>';

        $element.html(html);

        var data = $element.data();
        var jsoned = $.toJSON(data.defaults);
        $element.find('input').val(jsoned);

        this.update();
    };

    ChainOld.prototype.changed = function (select) {
        var $element = $(this.element);
        var data = $element.data();
        var $input = $element.find('input');
        var $value = $(select).val();

        var select_data = $(select).data();
        var select_index = select_data.index;

        if ($value == '') {
            this.remove_val.call(this, select_index);
        } else {
            this.val.call(this, select_index, $value);
        }

        // update
        this.update.call(this);

        // ajax
        this.ajax();
    };

    ChainOld.prototype.remove_box = function (box) {
        var $element = $(this.element);
        var $box = $(box);
        var height = $box.outerHeight();
        var width = $box.outerWidth();

        $box.animate({
            top: height,
            //width: width * 2,
            opacity: 0
        });
    };

    ChainOld.prototype.remove_val = function (index) {
        var $element = $(this.element);
        var $input = $element.find('input');
        var jsoned = $input.val();

        var values = $.parseJSON(jsoned);

        // remove
        values.splice(index, 1);

        $input.val($.toJSON(values));
    };

    ChainOld.prototype.insert_val = function (index, value) {
        var $element = $(this.element);
        var $input = $element.find('input');
        var jsoned = $input.val();

        var values = $.parseJSON(jsoned);

        // insert
        values.splice(index, 0, value);

        $input.val($.toJSON(values));
    };

    ChainOld.prototype.val = function () {
        var $element = $(this.element);
        var data = $element.data();
        var $input = $element.find('input');

        // get value
        if (arguments.length == 0) {
            return $input.val();
        }

        // set value
        else if (arguments.length == 1) {
            var value = arguments[0];

            if (typeof value != 'string') {
                value = $.toJSON(value);
            }

            $element.find('input').val(value);
            this.update();

            return this;
        }

        // set value to element
        else if (arguments.length == 2) {
            var element_index = arguments[0];
            var value = arguments[1];

            if (typeof element_index == 'string') {
                element_index = parseInt(element_index);
            }

            var current_values = $element.find('input').val();
            current_values = $.parseJSON(current_values);

            current_values[element_index] = value;

            var new_values = $.toJSON(current_values);

            $element.find('input').val(new_values);

            this.update();

            return this;
        }

    };

    ChainOld.prototype.ajax = function () {
        var self = this;
        var $element = $(this.element);
        var $input = $element.find('input');
        var data = $element.data();
        var url = decodeURIComponent(data.url);

        $.post(url, {value:$input.val()}, function(data, status, xhr) {
                if (!data.ok) {
                    console.error('Can not set property');
                    $input.val(old_value); // FIXME save old value
                    self.update.call(self);
                }
            },
            'json');
    };

    function Plugin(option) {
        return this.each(function () {
            new ChainOld(this);
        })
    }

    var old = $.fn.ChainOld;

    $.fn.ChainOld             = Plugin;
    $.fn.ChainOld.Constructor = ChainOld;

    $.fn.ChainOld.noConflict = function () {
        $.fn.ChainOld = old;
        return this
    }

}(jQuery);


jQuery(document).ready(function($) {
    $('.checkbox').CheckboxOld();
    $('.switcher').Switcher();
    $('.chain').ChainOld();
});
