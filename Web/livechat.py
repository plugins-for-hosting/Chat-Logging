try:
    assert __name__ == "TF2" or __name__ == "CS"
    gameinfo = __name__

except:
    from browser.widgets.dialog import InfoDialog # pylint: disable=import-error
    InfoDialog("Error", "variable 'gameinfo' must be either 'TF2' or 'CS'")
    raise ValueError("variable 'gameinfo' must be either 'TF2' or 'CS'")

import json
from browser import document, html, ajax, bind, window, timer # pylint: disable=import-error
from browser.widgets.dialog import Dialog, InfoDialog # pylint: disable=import-error
import steamid

def on_complete(req):
    if req.status == 200 or req.status == 0:
        data = json.loads(req.text)["data"]

        global last_msg_id
        last_msg_id = data["last_msg_id"]

        rows = data["data"]

        print(document.select("div.panel-body")[0])

        for value in rows:
            log_html = html.STRONG(value["html"], id=value["msg_id"], Class="class_chatlog")

            @bind(log_html, "click")
            def onclick(ev):
                steamid.prompt_steamid_dialog(ev.currentTarget.id, ev.clientX, ev.clientY)
            
            document.select("div.panel-body")[0] <= log_html

    timer.set_timeout(3000, timeout_loop)

def timeout_loop():
    global last_msg_id

    try:
        last_msg_id
    except NameError:
        url = "./api.php?live=" + gameinfo
    else:
        url = "./api.php?live=" + gameinfo + "&live_msg_id=" + str(msg_id)

    ajax.get(url=url, oncomplete=on_complete)

timeout_loop()