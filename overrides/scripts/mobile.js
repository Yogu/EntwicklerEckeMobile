(function($) {
	if (window.parent == window) {
		var container;
		var main;
		var page;
		var menu;
		function init() {
			container = $('<div>').attr('id', 'container').appendTo($('body'));
			$('body > *').not(container).appendTo(container);
			
			main = $('<div>').attr('id', 'main').insertBefore($('.overall'));
			page = $('<div>').attr('id', 'page').appendTo(main);
			$('.overall, p.copyright').appendTo(page);
			menu = $('<div>').attr('id', 'menu').appendTo(main);
			createMenu($('<ul>').appendTo(menu));
			
			var contentcover = $('<div>').addClass('cover').insertBefore(menu);
			contentcover.click(hideMenu);

			var menubutton = $('<a>').attr('id', 'menubutton').text('Menü').prependTo('.mainheader');
			menubutton.click(toggleMenu);
		}
		
		function hideMenu() {
			$('body').removeClass('menu-expanded');
			page.css('min-height', '0');
		}
		
		function showMenu() {
			$('body').addClass('menu-expanded');
			
			// Menu might be lager that content
			setTimeout(function() {
				page.css('min-height', menu.height()+'px');
			}, 0);
		}
		
		function toggleMenu() {
			if ($('body').hasClass('menu-expanded'))
				hideMenu();
			else
				showMenu();
		}
		
		function createMenu(menu) {
			var loggedIn = $('.mainmenu a[href^="login.php?logout"]').length > 0;
			
			$('<a>').appendTo($('<li>').appendTo(menu))
				.text('Suche')
				.css('background-image', 'url(/graphics/sitemap/search.png)')
				.attr('href', '/search.php');
			if (loggedIn) {
				$('<a>').appendTo($('<li>').appendTo(menu))
					.text('Meine Ecke')
					.css('background-image', 'url(/graphics/sitemap/my.png)')
					.attr('href', '/my.php');
				$('<a>').appendTo($('<li>').appendTo(menu))
					.text('Private Nachrichten')
					.css('background-image', 'url(/graphics/my/pn_small.png)')
					.attr('href', '/my.php');
				$('<a>').appendTo($('<li>').appendTo(menu))
					.text('Shoutbox')
					.attr('href', '/shoutbox.php');
			} else {
				$('<a>').appendTo($('<li>').appendTo(menu))
					.text('Login')
					.css('background-image', 'url(graphics/header/login.png)')
					.attr('href', '/login.php');
				$('<a>').appendTo($('<li>').appendTo(menu))
					.text('Registrieren')
					.css('background-image', 'url(/graphics/header/register.png)')
					.attr('href', '/profile.php?mode=register');
			}
			$('<a>').appendTo($('<li>').appendTo(menu))
				.text('Hilfe')
				.css('background-image', 'url(/graphics/sitemap/help.png)')
				.attr('href', '/sites.php?id=19&sub=,19');
			$('<a>').appendTo($('<li>').appendTo(menu))
				.text('Wer ist online?')
				.css('background-image', 'url(/graphics/sitemap/users_small.png)')
				.attr('href', '/viewonline.php');
			$('<a>').appendTo($('<li>').appendTo(menu))
				.text('Sitemap')
				.css('background-image', 'url(/graphics/header/sitemap.png)')
				.attr('href', '/sitemap.php');
			if (loggedIn)
				$('<a>').appendTo($('<li>').appendTo(menu))
					.text('Logout')
					.css('background-image', 'url(/graphics/header/logout.png)')
					.attr('href', $('.mainmenu a[href^="login.php?logout"]').attr('href'));
		}

		$(init);
	}
})(jQuery);