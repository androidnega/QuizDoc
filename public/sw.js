/* QuizSnap: service worker for exam reminder push notifications */
self.addEventListener('push', function (event) {
    if (!event.data) return;
    var data = {};
    try {
        data = event.data.json();
    } catch (e) {
        data = { title: 'QuizSnap', body: event.data.text() || 'Reminder' };
    }
    var title = data.title || 'Exam reminder';
    var body = data.body || 'You have an exam soon.';
    var options = {
        body: body,
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        tag: 'exam-reminder',
        renotify: true,
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
            var url = '/dashboard/calendar';
            for (var i = 0; i < windowClients.length; i++) {
                if (windowClients[i].url.indexOf(url) !== -1 && 'focus' in windowClients[i]) {
                    return windowClients[i].focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow('/dashboard');
            }
        })
    );
});
