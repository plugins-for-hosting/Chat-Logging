try:
    assert __name__ == "TF2" or __name__ == "CS"

except:
    from browser.widgets.dialog import InfoDialog # pylint: disable=import-error
    InfoDialog("Error", "variable 'gameinfo' must be either 'TF2' or 'CS'")
    raise ValueError("variable 'gameinfo' must be either 'TF2' or 'CS'")

import json
from browser import document, html, ajax, bind, console, window # pylint: disable=import-error
from browser.widgets.dialog import Dialog, InfoDialog # pylint: disable=import-error
import steamid

def on_complete(req):


