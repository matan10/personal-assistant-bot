import requests
import json
import sys

class me_app:
    def __init__(self, phone_number):
        self.phone_number = phone_number
    
        # URLs
        self.main_url = "https://app.mobile.me.app"
        self.auth_url = "/auth/authorization/login/"
        self.search_url = "/main/contacts/search/?phone_number="
        
        # Tokens
        self.bearer_token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbl90eXBlIjoiYWNjZXNzIiwiZXhwIjoxNjA1OTA3MjAyLCJqdGkiOiI5Yzk3Yjk1OTRlOTk0YzVlYjUyMWRkMjY3YjAwZjRjZiIsInV1aWQiOiI0ZTg1ODk0ZS00MTQ4LTRjYjItOTMzNi03M2Y0ZWUyOGI4YzgiLCJwaG9uZV9udW1iZXIiOiI5NzI1ODU1MDQxMjQifQ.LMweEfpipQnTFqZ9NNdrSyMPPopqv4iUHMPYorI70EU"
        self.login_data = {"pwd_token":"6ebccbc4-cdb3-4f60-883d-b8031513b2cb","phone_number":"972585504124"}
        
        self.authentication()

    def authentication(self):
        auth_headers = {
                    "Content-Type": "application/json; charset=UTF-8",
                    "Content-Length": "82",
                    "Connection": "close",
                    "Accept-Encoding": "gzip, deflate",
                    "User-Agent": "okhttp/3.14.7"}

        url = self.main_url + self.auth_url
        res = requests.post(url, headers=auth_headers, json=self.login_data).json()
        self.bearer_token = res["access"]
        
        self.search_phone()

    def search_phone(self):
        search_phone_headers = {"Authorization": "Bearer " + self.bearer_token, "User-Agent": "okhttp/3.12.12"}

        url = self.main_url + self.search_url + self.phone_number
        res = requests.get(url, headers=search_phone_headers).json()

        if "contact" not in res:
            self.authentication()
        else:
            print(res)
        
if len(sys.argv) == 2:
    phone_number = sys.argv[1]
else:
    print("You have to supply a phone number as an argument.")

me_app(phone_number)