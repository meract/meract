Morph.http = {
  // 🔹 Асинхронный режим (на Fetch)
  async: {
    get(url, callback, headers = {}) {
      this._fetch(url, 'GET', null, headers, callback);
    },
    delete(url, callback, headers = {}) {
      this._fetch(url, 'DELETE', null, headers, callback);
    },
    post(url, data, callback, headers = {}) {
      this._fetch(url, 'POST', data, headers, callback);
    },
    put(url, data, callback, headers = {}) {
      this._fetch(url, 'PUT', data, headers, callback);
    },
    patch(url, data, callback, headers = {}) {
      this._fetch(url, 'PATCH', data, headers, callback);
    },
    
    async _fetch(url, method, data, headers, callback) {
      try {
        const options = {
          method,
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            ...headers,
          },
          body: null
        };

        // Конвертируем данные в URL-encoded строку
        if (data && ['POST', 'PUT', 'PATCH'].includes(method)) {
          options.body = this._urlEncode(data);
        }

        const response = await fetch(url, options);
        const headersArray = [...response.headers.entries()];
        const body = await response.text();

        callback({
          body,
          status: response.status,
          headers: headersArray,
          error: null,
          success: response.ok,
        });
      } catch (error) {
        callback({
          body: null,
          status: 0,
          headers: [],
          error: error.message,
          success: false,
        });
      }
    },
    
    // Конвертация объекта в URL-encoded строку
    _urlEncode(obj) {
      return Object.entries(obj)
        .map(([key, value]) => 
          `${encodeURIComponent(key)}=${encodeURIComponent(value)}`
        )
        .join('&');
    }
  },

  // 🔹 Синхронный режим (на XHR)
  sync: {
    get(url, headers = {}) {
      return this._xhr(url, 'GET', null, headers);
    },
    delete(url, headers = {}) {
      return this._xhr(url, 'DELETE', null, headers);
    },
    post(url, data, headers = {}) {
      return this._xhr(url, 'POST', data, headers);
    },
    put(url, data, headers = {}) {
      return this._xhr(url, 'PUT', data, headers);
    },
    patch(url, data, headers = {}) {
      return this._xhr(url, 'PATCH', data, headers);
    },
    
    _xhr(url, method, data, headers) {
      const xhr = new XMLHttpRequest();
      xhr.open(method, url, false);

      // Устанавливаем заголовки
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      for (const [key, value] of Object.entries(headers)) {
        xhr.setRequestHeader(key, value);
      }

      try {
        // Конвертируем данные и отправляем
        const encodedData = data && ['POST', 'PUT', 'PATCH'].includes(method)
          ? this._urlEncode(data)
          : null;
          
        xhr.send(encodedData);

        const headersText = xhr.getAllResponseHeaders();
        const headersArray = headersText.trim()
          .split(/[\r\n]+/)
          .map(line => line.split(': '));

        return {
          body: xhr.responseText || null,
          status: xhr.status,
          headers: headersArray,
          error: null,
          success: xhr.status >= 200 && xhr.status < 300,
        };
      } catch (error) {
        return {
          body: null,
          status: 0,
          headers: [],
          error: error.message,
          success: false,
        };
      }
    },
    
    _urlEncode(obj) {
      return Object.entries(obj)
        .map(([key, value]) => 
          `${encodeURIComponent(key)}=${encodeURIComponent(value)}`
        )
        .join('&');
    }
  }
};
