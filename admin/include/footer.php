	<div class="footer" >
		<div class="container" >
			 

			<b class="copyright">&copy; 2025 <?php echo APP_NAME; ?>  </b> All rights reserved.
		</div>
	</div>
	<div class="admin-fullscreen-backdrop" id="admin-fullscreen-backdrop"></div>
	<script>
	(function () {
		function firstDirectMatch(parent, selectorList) {
			if (!parent) {
				return null;
			}

			for (var child = parent.firstElementChild; child; child = child.nextElementSibling) {
				for (var i = 0; i < selectorList.length; i++) {
					if (child.matches && child.matches(selectorList[i])) {
						return child;
					}
				}
			}

			return null;
		}

		function wrapScrollableContent(moduleBody) {
			if (!moduleBody || firstDirectMatch(moduleBody, ['.admin-scroll-wrap'])) {
				return;
			}

			var target = firstDirectMatch(moduleBody, ['table', 'form.form-horizontal', 'form', '.table-scroll-wrap']);
			if (!target) {
				return;
			}

			if (target.classList && target.classList.contains('datatable-1')) {
				return;
			}

			if (target.classList && target.classList.contains('table-scroll-wrap')) {
				target.classList.add('admin-scroll-wrap');
				return;
			}

			var wrap = document.createElement('div');
			wrap.className = 'admin-scroll-wrap';
			target.parentNode.insertBefore(wrap, target);
			wrap.appendChild(target);
		}

		function attachFullscreenButton(module) {
			if (!module || module.getAttribute('data-admin-enhanced') === '1') {
				return;
			}

			var moduleHead = firstDirectMatch(module, ['.module-head']);
			var moduleBody = firstDirectMatch(module, ['.module-body']);
			if (!moduleHead || !moduleBody) {
				return;
			}

			var hasForm = moduleBody.querySelector('form');
			var hasTable = moduleBody.querySelector('table');
			if (!hasForm && !hasTable) {
				return;
			}

			wrapScrollableContent(moduleBody);

			var tools = document.createElement('div');
			tools.className = 'admin-module-tools';

			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'btn btn-small';
			button.textContent = 'View Full';
			button.addEventListener('click', function () {
				var backdrop = document.getElementById('admin-fullscreen-backdrop');
				var isOpen = module.classList.contains('module-fullscreen');

				if (isOpen) {
					module.classList.remove('module-fullscreen');
					document.body.classList.remove('admin-fullscreen-open');
					if (backdrop) {
						backdrop.classList.remove('is-open');
					}
					button.textContent = 'View Full';
				} else {
					document.querySelectorAll('.module.module-fullscreen').forEach(function (openModule) {
						openModule.classList.remove('module-fullscreen');
					});
					document.querySelectorAll('.admin-module-tools .btn').forEach(function (toolButton) {
						if (toolButton.textContent === 'Exit Full') {
							toolButton.textContent = 'View Full';
						}
					});
					module.classList.add('module-fullscreen');
					document.body.classList.add('admin-fullscreen-open');
					if (backdrop) {
						backdrop.classList.add('is-open');
					}
					button.textContent = 'Exit Full';
				}
			});

			tools.appendChild(button);
			moduleHead.appendChild(tools);
			module.setAttribute('data-admin-enhanced', '1');
		}

		function enhanceAdminModules() {
			document.querySelectorAll('.module').forEach(function (module) {
				attachFullscreenButton(module);
			});
		}

		document.addEventListener('DOMContentLoaded', enhanceAdminModules);
		document.addEventListener('click', function (event) {
			if (event.target && event.target.id === 'admin-fullscreen-backdrop') {
				document.querySelectorAll('.module.module-fullscreen').forEach(function (module) {
					module.classList.remove('module-fullscreen');
				});
				document.querySelectorAll('.admin-module-tools .btn').forEach(function (button) {
					if (button.textContent === 'Exit Full') {
						button.textContent = 'View Full';
					}
				});
				document.body.classList.remove('admin-fullscreen-open');
				event.target.classList.remove('is-open');
			}
		});
	}());
	</script>
