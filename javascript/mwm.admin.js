!function(e){e.entwine("ss",function(e){e("div.ss-upload-to-folder").entwine({onfileuploaddone:function(e,t){this.reloadGridField()},onfileuploaddestroy:function(e,t){this.reloadGridField()},reloadGridField:function(){var e=this;setTimeout(function(){var t=e.closest("form").find(".ss-gridfield");t.reload()},10)}}),e("form.uploadfield-form #ParentID .TreeDropdownField").entwine({onchange:function(){var t=this.closest("form").find("input.ss-upload-to-folder:first"),n=this;if(t.length){var a=t.parents(".ss-upload-to-folder:first");config=t.data("config"),config.url&&e.get(config.url.substring(0,config.url.indexOf("/upload"))+"?folder="+this.getValue(),function(e){a.replaceWith(n.addFolderIdToUrlsInHTML(e))})}},addFolderIdToUrlsInHTML:function(e){var t=this.getValue(),n={"\\/upload&quot;":"\\/upload?folder="+t+"&quot;","\\/attach&quot;":"\\/attach?folder="+t+"&quot;","\\/select&quot;":"\\/select?folder="+t+"&quot;","\\/fileexists&quot;":"\\/fileexists?folder="+t+"&quot;"};for(var a in n)n.hasOwnProperty(a)&&(e=e.replace(new RegExp(this.escapeRegExp(a),"g"),n[a]));return e},escapeRegExp:function(e){return e.replace(/([.*+?^=!:${}()|\[\]\/\\])/g,"\\$1")}}),e(".ss-tabset").entwine({onadd:function(){this._super(),window.location.hash&&this.data("tabs")&&e(window.location.hash).click()}}),e(".ss-tabset-goto").entwine({onclick:function(){window.location.hash&&e(window.location.hash).click()}})}),e.entwine("ss.tree",function(e){e(".cms .cms-tree").entwine({getTreeConfig:function(){var t=this,n=t.data("urlDeletepage"),a=this._super(),i=e(".cms-container");if(a.hasOwnProperty("contextmenu")){var s=a.contextmenu.items;a.contextmenu.items=function(a){var o=s(a),r=t.data("urlUnpublishpage"),l=t.data("urlPublishpage");return l&&(o.publish={label:ss.i18n._t("Tree.Publish","Publish"),action:function(n){var a=n.data("id"),s=[a];i.entwine(".ss").loadFragment(e.path.addSearchParams(ss.i18n.sprintf(l,a),t.data("extraParams")),"SiteTree").success(function(){t.updateNodesFromServer(s)})}}),r&&(o.unpublish={label:ss.i18n._t("Tree.Unpublish","Unpublish"),action:function(n){var a=n.data("id"),s=[a];i.entwine(".ss").loadFragment(e.path.addSearchParams(ss.i18n.sprintf(r,a),t.data("extraParams")),"SiteTree").success(function(){t.updateNodesFromServer(s)})}}),n&&!a.hasClass("nodelete")&&(o["delete"]={label:ss.i18n._t("Tree.Delete_Permanently","Delete permanently"),action:function(a){if(confirm(ss.i18n._t("CMSMAIN.DELETE_PERMANENTLY","Are you sure you want to delete this page permanently (aka no going back)?"))){var s=a.data("id");i.entwine(".ss").loadFragment(e.path.addSearchParams(ss.i18n.sprintf(n,s),t.data("extraParams")),"SiteTree").success(function(){var e=t.getNodeByID(s);e.length&&t.jstree("delete_node",e),i.entwine(".ss").reloadCurrentPanel()})}}}),o}}return a}})})}(jQuery);