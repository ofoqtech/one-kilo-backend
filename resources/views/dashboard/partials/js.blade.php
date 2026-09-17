<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- BEGIN: Vendor JS-->
<script src="{{ asset('dashboard') }}/app-assets/vendors/js/vendors.min.js"></script>
<!-- BEGIN Vendor JS-->


<!-- BEGIN: Page Vendor JS-->
<script src="{{ asset('dashboard') }}/app-assets/vendors/js/charts/apexcharts.min.js"></script>
<script src="{{ asset('dashboard') }}/app-assets/vendors/js/extensions/toastr.min.js"></script>
<script src="{{ asset('dashboard') }}/app-assets/js/scripts/extensions/ext-component-toastr.js"></script>

<!-- END: Page Vendor JS-->

<!-- BEGIN: Theme JS-->
<script src="{{ asset('dashboard') }}/app-assets/js/core/app-menu.js"></script>
<script src="{{ asset('dashboard') }}/app-assets/js/core/app.js"></script>
<!-- END: Theme JS-->

<!-- BEGIN: Page JS-->
<script src="{{ asset('dashboard') }}/app-assets/js/scripts/pages/dashboard-ecommerce.js"></script>
<!-- END: Page JS-->
<script src="{{ asset('dashboard') }}/app-assets/vendors/js/extensions/sweetalert2.all.min.js"></script>
<script src="{{ asset('dashboard') }}/app-assets/js/scripts/extensions/ext-component-sweet-alerts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js"
    integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>

{{-- file input to upload image and show it --}}
<script src="{{ asset('vendor/file-input/js/fileinput.min.js') }}"></script>
<script src="{{ asset('vendor/file-input/themes/fa5/theme.min.js') }}"></script>
@if (Config::get('app.locale') == 'ar')
    <script src="{{ asset('vendor/file-input/js/locales/LANG.js') }}"></script>
    <script src="{{ asset('vendor/file-input/js/locales/ar.js') }}"></script>
@endif
<script>
    var lang = "{{ app()->getLocale() }}";
    $(function() {
        $('#singel-image').fileinput({
            theme: 'fa5',
            language: lang,
            allowedFileTypes: ['image'],
            maxFileCount: 1,
            enableResumableUpload: false,
            showUpload: false,
            browseOnZoneClick: true,
        });
    });
</script>
<script>
    var lang = "{{ app()->getLocale() }}";
    $(function() {
        $('#multiple-images').fileinput({
            theme: 'fa5',
            language: lang,
            allowedFileTypes: ['image'],
            maxFileCount: 10,
            showUpload: false,
            showRemove: true,
            showCaption: true,
            showClose: false,
            browseOnZoneClick: true,
            fileActionSettings: {
                showZoom: true,
                showDrag: false,
            },
            initialPreviewAsData: true,
            overwriteInitial: false,
        });
    });
</script>

{{-- end file input to upload image and show it --}}

<script>
    (function () {
        var toggle = document.getElementById('ok-theme-toggle');
        if (!toggle) return;

        function syncIcons() {
            var isDark = document.documentElement.getAttribute('data-ok-theme') === 'dark';
            var moon = toggle.querySelector('.fa-moon');
            var sun = toggle.querySelector('.fa-sun');
            if (moon) moon.style.display = isDark ? 'none' : '';
            if (sun) sun.style.display = isDark ? '' : 'none';
        }

        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            var isDark = document.documentElement.getAttribute('data-ok-theme') === 'dark';
            if (isDark) {
                document.documentElement.removeAttribute('data-ok-theme');
                try { localStorage.setItem('ok-theme', 'light'); } catch (err) {}
            } else {
                document.documentElement.setAttribute('data-ok-theme', 'dark');
                try { localStorage.setItem('ok-theme', 'dark'); } catch (err) {}
            }
            syncIcons();
        });

        // FontAwesome replaces <i> with <svg> asynchronously, so wait a tick before the first sync.
        setTimeout(syncIcons, 300);
        window.addEventListener('load', syncIcons);
    })();
</script>

@auth('admin')
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    <script>
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: @json(config('broadcasting.connections.pusher.key')),
            cluster: @json(config('broadcasting.connections.pusher.options.cluster')),
            forceTLS: true,
            authEndpoint: '{{ url('/broadcasting/auth') }}',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            },
        });
    </script>
@endauth

@stack('js')
<script>
    $(window).on('load', function() {
        /* feather.replace removed */
    })
</script>


{{-- Sweetalert from delete confirmtion --}}
<script>
    // sweetalert from basic table and refresh the page
    document.addEventListener('click', function(event) {
        if (event.target.id === 'confirm-delete-text' || event.target.closest('#confirm-delete-text')) {
            const button = event.target.closest('#confirm-delete-text');
            const formId = button.getAttribute('data-form-id');

            Swal.fire({
                title: "{{ __('dashboard.are_you_sure') }}",
                text: "{{ __('dashboard.confirm_delete_message') }}",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "{{ __('dashboard.yes_delete') }}",
                cancelButtonText: "{{ __('dashboard.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        }
    });
    // sweetalert from datatable and refresh table only
    document.addEventListener('click', function(event) {
        if (event.target.id === 'confirm-text' || event.target.closest('#confirm-text')) {
            const button = event.target.closest('#confirm-text');
            const formId = button.getAttribute('data-form-id');
            const form = document.getElementById(formId);
            const actionUrl = form.getAttribute('action');

            Swal.fire({
                title: "{{ __('dashboard.are_you_sure') }}",
                text: "{{ __('dashboard.confirm_delete_message') }}",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "{{ __('dashboard.yes_delete') }}",
                cancelButtonText: "{{ __('dashboard.cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(actionUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                _method: 'DELETE'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: "{{ __('dashboard.success') }}",
                                    text: data.message,
                                    icon: "success",
                                    timer: 3000,
                                    showConfirmButton: true
                                });
                                $('#DataTables_Table').DataTable().ajax.reload(null, false);
                            } else {
                                Swal.fire("{{ __('dashboard.error') }}", data.message, "error");
                            }
                        })
                        .catch(error => {
                            Swal.fire("{{ __('dashboard.error') }}",
                                "{{ __('dashboard.error_occurred') }}", "error");
                        });
                }
            });
        }
    });
</script>
{{-- End sweetalert from delete confirmtion --}}


{{-- Ajax change status and message confirmation --}}
<script>
    $(document).on('click', '.change-status-btn', function(e) {
        e.preventDefault();
        let button = $(this);
        let url = button.data('url');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                if (response.status == true) {
                    Swal.fire({
                        position: 'top-start',
                        icon: 'success',
                        title: '{{ __('dashboard.status-change') }}',
                        showConfirmButton: false,
                        timer: 1500,
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                    if (response.new_status == 1) {
                        button
                            .removeClass('btn-warning')
                            .addClass('btn-success')
                            .text('{{ __('dashboard.active') }}');
                    } else {
                        button
                            .removeClass('btn-success')
                            .addClass('btn-warning')
                            .text('{{ __('dashboard.inactive') }}');
                    }
                } else {
                    Swal.fire({
                        position: 'top-start',
                        icon: 'error',
                        title: '{{ __('validation.something-valid') }}',
                        showConfirmButton: false,
                        timer: 1500,
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                }
            },
            error: function(error) {
                console.error(error);
                showMessage('An error occurred. Please try again.', 'error');
            },
        });
    });
</script>
{{-- End ajax change status and message confirmation --}}


{{-- Ajax change approved and message confirmation --}}
<script>
    $(document).on('click', '.change-approved-btn', function(e) {
        e.preventDefault();
        let button = $(this);
        let url = button.data('url');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                if (response.status) {
                    Swal.fire({
                        position: 'top-start',
                        icon: 'success',
                        title: '{{ __('dashboard.approved-change') }}',
                        showConfirmButton: false,
                        timer: 1500,
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                    if (response.new_approved == 1) {
                        button
                            .removeClass('btn-warning')
                            .addClass('btn-success')
                            .html('<i class="fa-solid fa-check"></i>');
                    } else {
                        button
                            .removeClass('btn-success')
                            .addClass('btn-warning')
                            .html('<i class="fa-solid fa-x"></i>');
                    }

                } else {
                    showMessage(response.message, 'error');
                }
            },
            error: function(error) {
                console.error(error);
                showMessage('An error occurred. Please try again.', 'error');
            },
        });
    });
</script>
{{-- End ajax change approved and message confirmation --}}


{{-- Optimize modal in livewire to open and close --}}
<script>
    window.addEventListener('createModalToggle', event => {
        $('#createModal').modal('toggle');
    })

    window.addEventListener('updateModalToggle', event => {
        $('#updateModal').modal('toggle');
    })

    window.addEventListener('deleteModalToggle', event => {
        $('#deleteModal').modal('toggle');
    })

    window.addEventListener('showModalToggle', event => {
        $('#showModal').modal('toggle');
    })

    // Livewire.on('changeStatus', data => {
    //     console.log('Received changeStatus event:', data);
    // });
</script>
{{-- End optimize modal in livewire to open and close --}}



<script>
    const Toast = Swal.mixin({
        toast: false,
        position: 'center',
        showConfirmButton: false,
        timer: 1500,
        timerProgressBar: false,
    });

    // Plays sound when a new notification arrives (triggered from Livewire)
    window.addEventListener('dashboard-new-notification', () => {
        const audio = document.getElementById('dashboard-notify-audio');
        if (audio) {
            try {
                audio.currentTime = 0;
                audio.play();
            } catch (e) {
                // Ignore autoplay restrictions
            }
        }

        // Optional toast
        Toast.fire({
            icon: 'info',
            title: @json(__('dashboard.new-notification-received'))
        });
    });

    window.addEventListener('notify', (event) => {
        const {
            type = 'info', // success | error | warning | info | question
                message = '', // نص الرسالة
                title = '', // عنوان اختياري
                toast = true, // لو true هنستخدم Toast
                position = 'center', // موضع التوست
                timer = 2500, // مدة الإغلاق التلقائي
                showConfirmButton = false // إظهار زر OK
        } = event.detail || {};

        if (toast) {
            Toast.update({
                position: position,
                timer: timer,
                showConfirmButton: showConfirmButton,
            });
            Toast.fire({
                icon: type,
                title: message || title || ''
            });
        } else {
            Swal.fire({
                icon: type,
                title: title || undefined,
                text: message || undefined,
                position: position,
                showConfirmButton: showConfirmButton,
                timer: timer,
            });
        }
    });
</script>
<script type="module">

    // TODO: Add SDKs for Firebase products that you want to use
    // https://firebase.google.com/docs/web/setup#available-libraries
    import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.0/firebase-app.js";
    import { getDatabase, query, ref, orderByChild, startAt, onChildAdded, limitToLast} from "https://www.gstatic.com/firebasejs/10.7.0/firebase-database.js";

    // Your web app's Firebase configuration
    // For Firebase JS SDK v7.20.0 and later, measurementId is optional
    const firebaseConfig = {
        apiKey: "AIzaSyApQN35hDL7ZVCYQYmvWzFkh8CD4xiHYsE",
        authDomain: "one-kilo-d1a84.firebaseapp.com",
        databaseURL: "https://one-kilo-d1a84-default-rtdb.firebaseio.com",
        projectId: "one-kilo-d1a84",
        storageBucket: "one-kilo-d1a84.firebasestorage.app",
        messagingSenderId: "993977701470",
        appId: "1:993977701470:web:6673ecfda54d0d78c6d0cf",
        measurementId: "G-162JPJ4LEY"
    };

    // Initialize Firebase
    const app = initializeApp(firebaseConfig);
    const db = getDatabase(app);

    let isInitialLoad = true;
    const lastSeen = Date.now();

    const ordersQuery = query(
        ref(db, "orders"),
        limitToLast(1)
    );

    let firstLoad = true;

    onChildAdded(ordersQuery, (snapshot) => {

        const order = snapshot.val();

        if (firstLoad) {
            firstLoad = false;
            return;
        }

        console.log("NEW ORDER:", order);
        showOrderToast(order);
        play();
    });


    function showOrderToast(order) {

        Swal.fire({
            icon: "info", // or "success", "warning", etc.
            position: "top-end",
            showConfirmButton: true,
            confirmButtonText: "OK",

            html: `
    <div style="direction:rtl;">
        <div style="margin-bottom:10px;">
            <b>رقم الطلب # ${order.id}</b>
        </div>

        <div style="margin-bottom:10px;">
            👤 اسم المستخدم: <b>${order.user_name}</b>
        </div>
    </div>
    `,
        });

    }


    let audioUnlocked = false;
    const orderSound = new Audio('/sounds/sounds.mp3');

    function unlockAudio() {
        if (audioUnlocked) return;

        orderSound.play().then(() => {
            orderSound.pause();
            orderSound.currentTime = 0;
            audioUnlocked = true;
        }).catch(() => {});
    }
function play(){
    orderSound.play()
}

    //document.addEventListener('click', play, { once: true });


    if (audioUnlocked) {
        orderSound.currentTime = 0;
        orderSound.play().catch(() => {});
    }

</script>
