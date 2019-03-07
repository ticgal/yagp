#!/bin/bash

xgettext *.php */*.php -o locales/yagp.pot -L PHP --add-comments=TRANS --from-code=UTF-8 --force-po \
    --keyword=_n:1,2 --keyword=__s --keyword=__ --keyword=_e --keyword=_x:1c,2 --keyword=_ex:1c,2 --keyword=_sx:1c,2 --keyword=_nx:1c,2,3 --keyword=_sn:1,2 \
    --copyright-holder "TICgal"

#Update main language
LANG=C msginit --no-translator -i locales/yagp.pot -l en_GB -o locales/en_GB.po