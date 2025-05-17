import argparse
import cloudscraper
import re
import json
import time
import base64
import pickle
import urllib.parse
import sys
import os
import gzip
import zlib

# headers = {
# #    "Referer": "https://cp.nextline.com.br",
# }
headers = {
    "accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
    "accept-encoding": "gzip, deflate",
    "accept-language": "pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
    "cache-control": "no-cache",
    "pragma": "no-cache",
    "priority": "u=0, i",
    "referer": "https://vjudge.net/status",
    "sec-ch-ua": '"Not(A:Brand";v="99", "Google Chrome";v="133", "Chromium";v="133"',
    "sec-ch-ua-mobile": "?0",
    "sec-ch-ua-platform": '"Linux"',
    "sec-fetch-dest": "document",
    "sec-fetch-mode": "navigate",
    "sec-fetch-site": "same-origin",
    "sec-fetch-user": "?1",
    "upgrade-insecure-requests": "1",
    "user-agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36"
}

def save_cookies(scraper, filename):
    """Salva os cookies da sessão em um arquivo."""
    with open(filename, "wb") as f:
        pickle.dump(scraper.cookies, f)

def load_cookies(scraper, filename):
    """Carrega os cookies salvos e os adiciona ao scraper."""
    if os.path.exists(filename):
        with open(filename, "rb") as f:
            cookies = pickle.load(f)
            scraper.cookies.update(cookies)


def loginScrapper(username, password,cookie_file):
    scraper = cloudscraper.create_scraper()
    load_cookies(scraper,cookie_file)
    response = scraper.get('https://vjudge.net/problem/data',params={
        'start': 0,
        'length': 20,
        'sortDir': 'asc',
        'sortCol': 6,
        'OJId': 'CodeForces',
        'title': '',
        'category': 'all'
    }, headers=headers)
    data = json.loads(response.text)
    if not response.ok or not data.get('data') or len(data.get('data')) == 0:    
        # Tenta fazer login denovo.
        url_login = "https://vjudge.net/user/login"

        dados_login = {"username": username, "password": password}
        response = scraper.post(url_login, data=dados_login, headers=headers)
        if not response.ok:
            print("Login failed!")
            exit(1)
        if response.text == 'Human verification failed':
            print("Human verification failed!")
            exit(2)
        
        save_cookies(scraper,cookie_file)
    return scraper

def testLogin(username, password, cookie_file):
    scraper = cloudscraper.create_scraper()
    print('Cookies em:' , cookie_file)
    load_cookies(scraper,cookie_file)
    response = scraper.get('https://vjudge.net/problem/data',params={
        'start': 0,
        'length': 20,
        'sortDir': 'asc',
        'sortCol': 6,
        'OJId': 'CodeForces',
        'title': '',
        'category': 'all'
    }, headers=headers)
    data = json.loads(response.text)
    if not response.ok or not data.get('data') or len(data.get('data')) == 0:    
        print('N logado')
        # Tenta fazer login denovo.
        url_login = "https://vjudge.net/user/login"

        dados_login = {"username": username, "password": password}
        response = scraper.post(url_login, data=dados_login, headers=headers)
        if not response.ok:
            print("Login failed!")
            exit(1)
        if response.text == 'Human verification failed':
            print("Human verification failed!")
            exit(2)
        
        save_cookies(scraper,cookie_file)
        print('Logado, cookies salvos')
    else:
        print('logado')


def oj():

    l = "POJ, ZOJ, UVALive, SGU, URAL, HUST, SPOJ, HDU, HYSBZ, UVA, CodeForces, Aizu, LightOJ, UESTC, NBUT, FZU, CSU, SCU, ACdream, CodeChef, Kattis, HihoCoder, HIT, HRBUST, EIJudge, AtCoder, HackerRank, 51Nod"
    l = list(map(lambda a: str(a).strip(), l.split(", ")))
    l.sort()
    
    print(json.dumps(l))


def problems(scraper, args):
    url_problems = 'https://vjudge.net/problem/data'

    response = scraper.get(url_problems,params={
        'start': args.page or 0,
        'length': args.length or 20,
        'sortDir': 'desc',
        'sortCol': 7,
        'OJId': args.oj,
        'title': args.title or '',
        'category': 'all'
    },headers=headers)

    if not response.ok:
        print("Problems failed!")
        exit(1)

    print(response.text)



def submitStatus(scraper, args):
    url_status = "https://vjudge.net/solution/data/"

    response = scraper.post(url_status + str(args.runId), data={'showCode': 'false'}, headers=headers)
    if response.status_code != 200:
        print("Erro ao buscar solução!")
        exit(1)
        
    print(response.text)

def problemInfo(scraper, args):
    url_problem = 'https://vjudge.net/problem/'+str(args.oj)+'-'+str(args.probNum)
    response = scraper.get(url_problem,headers=headers)
    if not response.ok:
        print("Unable to load!")
        exit(1)
    html = response.text

    dataJson = re.search(r'<textarea[^>]*name="dataJson"[^>]*>(.*?)</textarea>', html, re.DOTALL)
    if not dataJson:
        print("Não foi possivel encontrar dataJson!")
        exit(1)
    dataJson = dataJson.group(1).strip()
    dataJson = json.loads(dataJson)
    for key in dataJson[]:
        print(key,dataJson[key]) 
    src = re.search(r'<iframe[^>]*id="frame-description"[^>]*src="([^"]+)"', html)
    if not src:
        #response = scraper.get('https://vjudge.net/problem/description/2494244000000000')
        #print(response.text)
        print("Não foi possivel encontrar src!")
        exit(1)
    src = src.group(1).strip()
    problem_url = 'https://vjudge.net' + src



    print(json.dumps({
        'vjudge_url': problem_url,
        'languages': dataJson['languages'],

    }))

def submit(scraper,args):
    def encode_source(file_path):
        with open(file_path, "r", encoding="utf-8") as file:
            source_code = file.read()
        url_encoded = urllib.parse.quote(source_code)
        base64_encoded = base64.b64encode(url_encoded.encode()).decode()
        return base64_encoded

    
    
    url_formulario = "https://vjudge.net/problem/submit"


    if response.status_code != 200:
        print("Login failed!")
        exit(1)
    
    encoded_source = encode_source(args.source)
    
    dados_formulario = {
        "method": 0,
        "language": args.language,
        "open": 1,
        "source": encoded_source,
        "oj": args.oj,
        "probNum": args.probNum,
    }
    
    response = scraper.post(url_formulario, data=dados_formulario, headers=headers)
    print("Formulário enviado:", response.status_code)
    if response.status_code != 200:
        exit(1)
    
    run_id = json.loads(response.text).get('runId')
    if not run_id:
        print(response.text)
        print("Erro ao obter runId")
        exit(2)
    
    print(run_id)


def main():
    parser = argparse.ArgumentParser(description="Ferramenta para interagir com o VJudge")
    
    subparsers = parser.add_subparsers(dest="command", required=True)
    
    # Comando "problems"
    parser_problems = subparsers.add_parser("problems", help="Listar problemas de um contest")
    parser_problems.add_argument("--username", required=True, help="VJudge username")
    parser_problems.add_argument("--password", required=True, help="VJudge password")
    parser_problems.add_argument("--cookies", required=False, help="Path to cookie file")
    parser_problems.add_argument("--oj", required=True, help="Online Judge name (e.g., CodeForces)")
    parser_problems.add_argument("--title", required=False, help="Search title")
    parser_problems.add_argument("--page", required=False, help="Page number")
    parser_problems.add_argument("--length", required=False, help="Number of problems per page")
    

    # Comando "oj"
    parser_oj = subparsers.add_parser("oj", help="Listar online judges diponíveis")

    # Comando "submitStatus"
    parser_submit_status = subparsers.add_parser("submitStatus", help="Listar online judges diponíveis")
    parser_submit_status.add_argument("--runId", required=True, help="Submission Id to check")
    parser_submit_status.add_argument("--username", required=True, help="VJudge username")
    parser_submit_status.add_argument("--password", required=True, help="VJudge password")
    parser_submit_status.add_argument("--cookies", required=False, help="Path to cookie file")
    
    # Comando "submit"
    parser_submit = subparsers.add_parser("submit", help="Listar submissões de um usuário")
    parser_submit.add_argument("--username", required=True, help="VJudge username")
    parser_submit.add_argument("--password", required=True, help="VJudge password")
    parser_submit.add_argument("--cookies", required=False, help="Path to cookie file")
    parser_submit.add_argument("--oj", required=True, help="Online Judge name (e.g., CodeForces)")
    parser_submit.add_argument("--probNum", required=True, help="Problem number")
    parser_submit.add_argument("--language", type=int, required=True, help="Language ID")
    parser_submit.add_argument("--source", required=True, help="Path to source code file")

    parser_test_login = subparsers.add_parser("testLogin", help="Testar login e cookies")
    parser_test_login.add_argument("--username", required=True, help="VJudge username")
    parser_test_login.add_argument("--password", required=True, help="VJudge password")
    parser_test_login.add_argument("--cookies", required=False, help="Path to cookie file")
    
    parser_problem_info = subparsers.add_parser("problemInfo", help="Get problem info")
    parser_problem_info.add_argument("--username", required=True, help="VJudge username")
    parser_problem_info.add_argument("--password", required=True, help="VJudge password")
    parser_problem_info.add_argument("--cookies", required=False, help="Path to cookie file")
    parser_problem_info.add_argument("--oj", required=True, help="Online Judge name (e.g., CodeForces)")
    parser_problem_info.add_argument("--probNum", required=True, help="Problem number")


    args = parser.parse_args()
    
    COOKIES_FILE = args.cookies if hasattr(args, "cookies") and args.cookies else "cookies.pkl"
    match (args.command):
        case "submit": submit(loginScrapper(args.username,args.password,COOKIES_FILE),args)
        case "oj": oj()
        case "problemInfo": problemInfo(loginScrapper(args.username,args.password,COOKIES_FILE),args)
        case "problems": problems(loginScrapper(args.username,args.password,COOKIES_FILE),args)
        case "submitStatus": submitStatus(loginScrapper(args.username,args.password,COOKIES_FILE),args)
        case "testLogin": testLogin(args.username,args.password,COOKIES_FILE)
        case _: 
            print("Invalid function, avaliable functions: submit")
            exit(3)

if __name__ == "__main__":
    main()
