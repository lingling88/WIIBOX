/*! jquery-html5Validate.js 基于HTML5表单验证的jQuery插件
 * 支持type="email", type="number", type="tel", type="url", type="zipcode", 以及多type, 如type="email|tel". 支持 step, min, max, required, pattern, multiple
 * 有4个自定义扩展属性 data-key, data-target, data-min, data-max
 * 文档：http://www.zhangxinxu.com/wordpress/?p=2857
 * 如果您有任何问题，可以邮件至zhangxinxu@zhangxinxu.com
 * create by zhangxinxu 2012-12-05   
 * v1.0 beta
 		on 2012-12-05 创建
 		on 2012-12-06 兼容性调试
		on 2012-12-14 增加对multiple属性支持
		              testRemind方法的最大宽度限制
		on 2012-12-17 增加submitEnabled参数
		on 2012-12-19 暴露testRemind方法的CSS参数
		on 2012-12-20 testRemind尖角设置overflow: hidden for IE6
	v1.0 publish on 2012-12-20
	v1.2 on 2012-12-21 尖角实际高度可能覆盖单复选框的问题，做了高度限制处理
	v1.2.1 on 2013-03-20 增加hidden提示时候的定位，以便在多屏下的时候提示可见
	v1.3 on 2013-06-19 新增validate参数，应对其他一些自定义的验证
**/
(function(a,b){DBC2SBC=function(f){var c="",d,e;for(d=0;d<f.length;d++){e=f.charCodeAt(d);if(e>=65281&&e<=65373){c+=String.fromCharCode(f.charCodeAt(d)-65248)}else{if(e==12288){c+=String.fromCharCode(f.charCodeAt(d)-12288+32)}else{c+=f.charAt(d)}}}return c};a.testRemind=(function(){var d=a(window).width();var f=function(g){if(!g||!g.target){return}if(g.target.id!==a.testRemind.id&&a(g.target).parents("#"+a.testRemind.id).length===0){a.testRemind.hide()}},e=function(g){if(!g||!g.target){return}if(g.target.tagName.toLowerCase()!=="body"){a.testRemind.hide()}},c=function(){if(!a.testRemind.display){return}var g=a(window).width();if(Math.abs(d-g)>20){a.testRemind.hide();d=g}};return{id:"validateRemind",display:false,css:{},hide:function(){a("#"+this.id).remove();this.display=false;a(document).unbind({mousedown:f,keydown:e});a(window).unbind("resize",c)},bind:function(){a(document).bind({mousedown:f,keydown:e});a(window).bind("resize",c)}}})();OBJREG={EMAIL:"^[a-z0-9._%-]+@([a-z0-9-]+\\.)+[a-z]{2,4}$",NUMBER:"^\\-?\\d+(\\.\\d+)?$",URL:"^(http|https|ftp)\\:\\/\\/[a-z0-9\\-\\.]+\\.[a-z]{2,3}(:[a-z0-9]*)?\\/?([a-z0-9\\-\\._\\?\\,\\'\\/\\\\\\+&amp;%\\$#\\=~])*$",TEL:"^1\\d{10}$",ZIPCODE:"^\\d{6}$",prompt:{radio:"请选择一个选项",checkbox:"如果要继续，请选中此框",select:"请选择列表中的一项",email:"请输入电子邮件地址",url:"请输入网站地址",tel:"请输入手机号码",number:"请输入数值",date:"请输入日期",pattern:"内容格式不符合要求",empty:"请填写此字段",multiple:"多条数据使用逗号分隔"}};a.html5Attr=function(e,c){if(!e||!c){return b}if(document.querySelector){return a(e).attr(c)}else{var d;d=e.getAttributeNode(c);return d&&d.nodeValue!==""?d.nodeValue:b}};a.html5Validate=(function(){return{isSupport:(function(){return a('<input type="email">').attr("type")==="email"})(),isEmpty:function(e,d){d=d||a.html5Attr(e,"placeholder");var c=e.value;if(e.type!=="password"){c=a.trim(c)}if(c===""||c===d){return true}return false},isRegex:function(k,i,e){var d=k.value,g=d,h=k.getAttribute("type")+"";h=h.replace(/\W+$/,"");if(h!=="password"){g=DBC2SBC(a.trim(d));g!==d&&a(k).val(g)}i=i||(function(){return a.html5Attr(k,"pattern")})()||(function(){return h&&a.map(h.split("|"),function(m){var l=OBJREG[m.toUpperCase()];if(l){return l}}).join("|")})();if(g===""||!i){return true}var f=a(k).hasProp("multiple"),j=new RegExp(i,e||"i");if(f&&!/^number|range$/i.test(h)){var c=true;a.each(g.split(","),function(l,m){m=a.trim(m);if(c&&!j.test(m)){c=false}});return c}else{return j.test(g)}return true},isOverflow:function(g){if(!g){return false}var h=a(g).attr("min"),d=a(g).attr("max"),f,e,c;value=g.value;if(!h&&!d){e=a(g).attr("data-min"),c=a(g).attr("data-max");if(e&&value.length<e){a(g).testRemind("至少输入"+e+"个字符");g.focus()}else{if(c&&value.length>c){a(g).testRemind("最多输入"+c+"个字符");a(g).selectRange(c,value.length)}else{return false}}}else{value=Number(value);f=Number(a(g).attr("step"))||1;if(h&&value<h){a(g).testRemind("值必须大于或等于"+h)}else{if(d&&value>d){a(g).testRemind("值必须小于或等于"+d)}else{if(f&&!/^\d+$/.test(Math.abs((value-h||0))/f)){a(g).testRemind("值无效")}else{return false}}}g.focus();g.select()}return true},isAllpass:function(e,d){if(!e){return true}var g={labelDrive:true};params=a.extend({},g,d||{});if(e.size&&e.size()==1&&e.get(0).tagName.toLowerCase()=="form"){e=e.find(":input")}else{if(e.tagName&&e.tagName.toLowerCase()=="form"){e=a(e).find(":input")}}var c=this;var f=true,h=function(k,n,s){var q=a(k).attr("data-key"),o=a("label[for='"+k.id+"']"),r="",p;if(params.labelDrive){p=a.html5Attr(k,"placeholder");o.each(function(){var t=a(this).text();if(t!==p){r+=t.replace(/\*|:|：/g,"")}})}if(a(k).isVisible()){if(n=="radio"||n=="checkbox"){a(k).testRemind(OBJREG.prompt[n],{align:"left"});k.focus()}else{if(s=="select"||s=="empty"){a(k).testRemind((s=="empty"&&r)?"您尚未输入"+r:OBJREG.prompt[s]);k.focus()}else{if(/^range|number$/i.test(n)&&Number(k.value)){a(k).testRemind("值无效");k.focus();k.select()}else{var m=OBJREG.prompt[n]||OBJREG.prompt.pattern;if(r){m="您输入的"+r+"格式不准确"}if(n!="number"&&a(k).hasProp("multiple")){m+="，"+OBJREG.prompt.multiple}a(k).testRemind(m);k.focus();k.select()}}}}else{var j=a(k).attr("data-target");var l=a("#"+j);if(l.size()==0){l=a("."+j)}var i="您尚未"+(q||(s=="empty"?"输入":"选择"))+((!/^radio|checkbox$/i.test(n)&&r)||"该项内容");if(l.size()){if(l.offset().top<a(window).scrollTop()){a(window).scrollTop(l.offset().top-50)}l.testRemind(i)}else{alert(i)}}return false};e.each(function(){var l=this,k=l.getAttribute("type"),j=l.tagName.toLowerCase(),m=a(this).hasProp("required");if(k){var p=k.replace(/\W+$/,"");if(!params.hasTypeNormally&&a.html5Validate.isSupport&&k!=p){try{l.type=p}catch(o){}}k=p}if(f==false||l.disabled||k=="submit"||k=="reset"||k=="file"||k=="image"){return}if(k=="radio"&&m){var n=l.name?a("input[type='radio'][name='"+l.name+"']"):a(l),i=false;n.each(function(){if(i==false&&a(this).attr("checked")){i=true}});if(i==false){f=h(n.get(0),k,j)}}else{if(k=="checkbox"&&m&&!a(l).attr("checked")){f=h(l,k,j)}else{if(j=="select"&&m&&!l.value){f=h(l,k,j)}else{if((m&&c.isEmpty(l))||!(f=c.isRegex(l))){f?h(l,k,"empty"):h(l,k,j);f=false}else{if(c.isOverflow(l)){f=false}}}}}});return f}}})();a.fn.extend({isVisible:function(){return a(this).attr("type")!=="hidden"&&a(this).css("display")!=="none"&&a(this).css("visibility")!=="hidden"},hasProp:function(g){if(typeof g!=="string"){return b}var f=false;if(document.querySelector){var c=a(this).attr(g);if(c!==b&&c!==false){f=true}}else{var e=a(this).get(0).outerHTML,d=e.slice(0,e.search(/\/?['"]?>(?![^<]*<['"])/));f=new RegExp("\\s"+g+"\\b","i").test(d)}return f},selectRange:function(f,c){var e=a(this).get(0);if(a.browser.msie){var d=e.createTextRange();d.collapse(true);d.moveEnd("character",c);d.moveStart("character",f);d.select()}else{e.focus();e.setSelectionRange(f,c)}return this},testRemind:function(j,n){var f={size:6,align:"center",css:{maxWidth:280,backgroundColor:"#FFFFE0",borderColor:"#F7CE39",color:"#333",fontSize:"12px",padding:"5px 10px",zIndex:202}};n=n||{};n.css=a.extend({},f.css,n.css||a.testRemind.css);var e=a.extend({},f,n||{});if(!j||!a(this).isVisible()){return}var d={center:"50%",left:"15%",right:"85%"},i=d[e.align]||"50%";e.css.position="absolute";e.css.top="-99px";e.css.border="1px solid "+e.css.borderColor;if(a("#"+a.testRemind.id).size()){a.testRemind.hide()}this.remind=a('<div id="'+a.testRemind.id+'">'+j+"</div>").css(e.css);a(document.body).append(this.remind);var m;if(!window.XMLHttpRequest&&(m=parseInt(e.css.maxWidth))&&this.remind.width()>m){this.remind.width(m)}var g=a(this).offset(),l="top";if(!g){return a(this)}var k=g.top-this.remind.outerHeight()-e.size;if(k<a(document).scrollTop()){l="bottom";k=g.top+a(this).outerHeight()+e.size}var c=function(t){var s="transparent",o="dashed",p="solid";var r={},u={width:0,height:0,overflow:"hidden",borderWidth:e.size+"px",position:"absolute"},q={};if(t==="before"){r={top:{borderColor:[e.css.borderColor,s,s,s].join(" "),borderStyle:[p,o,o,o].join(" "),top:0},bottom:{borderColor:[s,s,e.css.borderColor,""].join(" "),borderStyle:[o,o,p,o].join(" "),bottom:0}}}else{if(t==="after"){r={top:{borderColor:e.css.backgroundColor+["",s,s,s].join(" "),borderStyle:[p,o,o,o].join(" "),top:-1},bottom:{borderColor:[s,s,e.css.backgroundColor,""].join(" "),borderStyle:[o,o,p,o].join(" "),bottom:-1}}}else{r=null;u=null;q=null;return null}}q=a.extend({},r[l],u);return a("<"+t+"></"+t+">").css(q)};var h={width:2*e.size,left:i,marginLeft:(-1*e.size)+"px",height:e.size,textIndent:0,overflow:"hidden",position:"absolute"};if(l=="top"){h.bottom=-1*e.size}else{h.top=-1*e.size}this.remind.css({left:g.left,top:k,marginLeft:a(this).outerWidth()*0.5-this.remind.outerWidth()*parseInt(i)/100}).prepend(a("<div></div>").css(h).append(c("before")).append(c("after")));a.testRemind.display=true;a.testRemind.bind();return a(this)},html5Validate:function(g,c){var e={novalidate:true,submitEnabled:true,validate:function(){return true}};var f=a.extend({},e,c||{});var d=a(this).find(":input");if(a.html5Validate.isSupport){if(f.novalidate){a(this).attr("novalidate","novalidate")}else{d.each(function(){var h=this.getAttribute("type")+"",j=h.replace(/\W+$/,"");if(h!=j){try{this.type=j}catch(i){}}});f.hasTypeNormally=true}}if(f.submitEnabled){a(this).find(":disabled").each(function(){if(/^image|submit$/.test(this.type)){a(this).removeAttr("disabled")}})}a(this).bind("submit",function(){if(a.html5Validate.isAllpass(d,f)&&f.validate()&&a.isFunction(g)){g.call(this)}return false});return a(this)}})})(jQuery);