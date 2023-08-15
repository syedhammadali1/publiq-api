import Echo from 'laravel-echo';

let post = (window.laravel_echo_port) ? (":" + window.laravel_echo_port) : ''
window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: window.location.hostname + post
});
