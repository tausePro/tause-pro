<script>
    (() => {
        const currentTheme = document.querySelector('body').getAttribute('data-theme');

        localStorage.setItem('lqdNavbarShrinked', true);

        document.body.classList.add("navbar-shrinked");
    })();
</script>

@if (Route::currentRouteName() === 'dashboard.user.generator.index')
    <script>
        document.body.classList.add('lqd-page-generator-v2');
    </script>
@endif
