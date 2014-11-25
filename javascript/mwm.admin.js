(function ($) {
	$.entwine('ss', function($) {
		$('.ss-tabset').entwine({
			onadd: function() {
				this._super();

				if(window.location.hash && this.data('tabs')) {
					$(window.location.hash).click();
				}
			}
		});

		$('.ss-tabset-goto').entwine({
			onclick: function() {
				if(window.location.hash) {
					$(window.location.hash).click();
				}
			}
		});
	});

	$.entwine('ss.tree', function ($) {
		$('.cms .cms-tree').entwine({
			getTreeConfig : function () {
				var self = this,
					del = self.data('urlDeletepage'),
					config = this._super(),
					cms = $('.cms-container');

				if (config.hasOwnProperty('contextmenu')) {
					var _items = config.contextmenu.items;

					config.contextmenu.items = function (node) {
						var menu = _items(node);

						//if (node.hasClass('versioned')) {
							var unpublish = self.data('urlUnpublishpage'),
								publish = self.data('urlPublishpage');

							if (publish) {
								menu.publish = {
									'label'  : ss.i18n._t('Tree.Publish', 'Publish'),
									'action' : function (obj) {
										var id = obj.data('id'),
											ids = [id];

										cms.entwine('.ss').loadFragment(
												$.path.addSearchParams(
													ss.i18n.sprintf(publish, id),
													self.data('extraParams')
												), 'SiteTree'
											).success(function () {
												self.updateNodesFromServer(ids);
											});
									}
								};
							}

							if (unpublish) {
								menu.unpublish = {
									'label'  : ss.i18n._t('Tree.Unpublish', 'Unpublish'),
									'action' : function (obj) {
										var id = obj.data('id'),
											ids = [id];

										cms.entwine('.ss').loadFragment(
												$.path.addSearchParams(
													ss.i18n.sprintf(unpublish, id),
													self.data('extraParams')
												), 'SiteTree'
											).success(function () {
												self.updateNodesFromServer(ids);
											});
									}
								};
							}
						//}

						if (del && !node.hasClass('nodelete')) {
							menu.delete = {
								'label'  : ss.i18n._t('Tree.Delete_Permanently', 'Delete permanently'),
								'action' : function (obj) {
									if (confirm(ss.i18n._t('CMSMAIN.DELETE_PERMANENTLY', 'Are you sure you want to delete this page permanently (aka no going back)?'))) {
										var id = obj.data('id');

										cms.entwine('.ss').loadFragment(
												$.path.addSearchParams(
													ss.i18n.sprintf(del, id),
													self.data('extraParams')
												), 'SiteTree'
											).success(function () {
												var node = self.getNodeByID(id);
												if(node.length)
													self.jstree('delete_node', node);

												cms.entwine('.ss').reloadCurrentPanel();
											});
									}
								}
							};
						}

						return menu;
					};
				}

				return config;
			}
		});
	});
})(jQuery);