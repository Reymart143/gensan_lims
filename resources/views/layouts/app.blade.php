@include('layouts.header')
  @include('layouts.sidebar')
    @include('layouts.navbar')
      <script src="https://code.jquery.com/jquery-3.3.1.min.js" crossorigin="anonymous"></script>
      @yield('content')
      <script>
        document.querySelectorAll('[data-table-search]').forEach(function (input) {
          const params = new URLSearchParams(window.location.search);
          input.value = params.get('search') || '';
          let searchTimer;

          input.addEventListener('input', function () {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(function () {
              const url = new URL(window.location.href);
              const query = input.value.trim();
              if (query) url.searchParams.set('search', query);
              else url.searchParams.delete('search');
              url.searchParams.delete('page');
              window.location.assign(url.toString());
            }, 600);
          });
        });
      </script>
@include('layouts.footer')
  
