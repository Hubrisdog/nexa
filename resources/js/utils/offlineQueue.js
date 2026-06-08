import axios from 'axios';

const QUEUE_KEY = 'nexa_offline_mutations';

export function getQueue() {
    try {
        return JSON.parse(localStorage.getItem(QUEUE_KEY)) || [];
    } catch (e) {
        return [];
    }
}

export function saveQueue(queue) {
    localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
}

export function addToQueue(config) {
    const queue = getQueue();
    queue.push({
        method: config.method,
        url: config.url,
        data: config.data,
        headers: config.headers,
        timestamp: Date.now(),
    });
    saveQueue(queue);
}

export async function syncQueue(onSyncSuccess, onSyncError) {
    const queue = getQueue();
    if (queue.length === 0) return;

    console.log(`Syncing ${queue.length} offline mutations...`);
    const remaining = [...queue];

    for (let i = 0; i < queue.length; i++) {
        const item = queue[i];
        try {
            await axios({
                method: item.method,
                url: item.url,
                data: item.data,
                headers: {
                    ...item.headers,
                    'X-Nexa-Offline-Sync': 'true',
                    'X-Nexa-Offline-Timestamp': item.timestamp.toString()
                }
            });
            remaining.shift();
            saveQueue(remaining);
            if (onSyncSuccess) onSyncSuccess(item);
        } catch (error) {
            console.error('Failed to sync offline mutation', item, error);
            if (onSyncError) onSyncError(item, error);
            break;
        }
    }
}

export function registerOfflineInterceptor(onOfflineTriggered, onSyncCompleted) {
    axios.interceptors.request.use(
        (config) => {
            const isMutation = ['post', 'put', 'delete', 'patch'].includes(config.method?.toLowerCase());
            const isSyncRequest = config.headers?.['X-Nexa-Offline-Sync'] === 'true';

            if (isMutation && !isSyncRequest && !navigator.onLine) {
                console.log(`Offline: queueing mutation request to ${config.url}`);
                addToQueue({
                    method: config.method,
                    url: config.url,
                    data: config.data,
                    headers: config.headers
                });
                
                if (onOfflineTriggered) {
                    onOfflineTriggered(config);
                }

                return Promise.reject({
                    isOfflineQueue: true,
                    message: 'Request queued offline',
                    config
                });
            }
            return config;
        },
        (error) => Promise.reject(error)
    );

    axios.interceptors.response.use(
        (response) => response,
        async (error) => {
            const config = error.config;
            const isMutation = config && ['post', 'put', 'delete', 'patch'].includes(config.method?.toLowerCase());
            const isSyncRequest = config?.headers?.['X-Nexa-Offline-Sync'] === 'true';

            if (isMutation && !isSyncRequest && (!error.response || error.code === 'ERR_NETWORK' || !navigator.onLine)) {
                console.log(`Network error: queueing mutation request to ${config.url}`);
                addToQueue({
                    method: config.method,
                    url: config.url,
                    data: typeof config.data === 'string' ? JSON.parse(config.data) : config.data,
                    headers: config.headers
                });

                if (onOfflineTriggered) {
                    onOfflineTriggered(config);
                }

                return Promise.resolve({
                    data: {
                        success: true,
                        is_offline_simulated: true,
                        message: 'Saved changes locally (offline)'
                    }
                });
            }
            return Promise.reject(error);
        }
    );

    window.addEventListener('online', () => {
        syncQueue(
            (syncedItem) => {
                console.log('Successfully synced:', syncedItem);
            },
            (failedItem, err) => {
                console.error('Failed to sync:', failedItem, err);
            }
        ).then(() => {
            if (onSyncCompleted) {
                onSyncCompleted();
            }
        });
    });
}
