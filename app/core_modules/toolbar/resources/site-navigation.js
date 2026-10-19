/** Native menus with responsive disclosure, Escape and outside-click dismissal. @author Derek Keats */
(() => {
  document.querySelectorAll('.chisimba-site-navigation').forEach(nav => {
    const shell = nav.querySelector('.chisimba-site-navigation__shell');
    const narrow = matchMedia('(max-width: 760px)');
    const resize = () => {
      const hadFocus = shell.contains(document.activeElement);
      shell.open = !narrow.matches;
      if (!shell.open && hadFocus) shell.querySelector('summary').focus();
    };
    resize(); narrow.addEventListener('change', resize);
    nav.addEventListener('keydown', event => {
      if (event.key !== 'Escape') return;
      const group = event.target.closest('.chisimba-site-navigation__group[open]');
      const target = group || (narrow.matches && shell.open ? shell : null);
      if (target) { target.open = false; target.querySelector('summary').focus(); event.preventDefault(); }
    });
    nav.addEventListener('click', event => {
      const summary = event.target.closest('.chisimba-site-navigation__group > summary');
      if (!summary) return;
      nav.querySelectorAll('.chisimba-site-navigation__group[open]').forEach(group => {
        if (group !== summary.parentElement) group.open = false;
      });
    });
    document.addEventListener('click', event => {
      if (!nav.contains(event.target)) nav.querySelectorAll('.chisimba-site-navigation__group[open]').forEach(group => { group.open = false; });
    });
  });
})();
