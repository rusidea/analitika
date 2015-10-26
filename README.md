Журнал "Аналитика"
==================

Полная официальная версия журнала ["Аналитика"](http://analitika.rusidea.info/).

Инструкции по запуску журнала и публикации в сети интернет см. в файле `INSTALL.txt`.

### Процедуры Git

**Получить журнал и все выпуски аналитики на локальный компьютер:**  
`git clone --recursive https://github.com/rusidea/analitika.git`

**Обновить журнал и выпуски аналитики:**  
`git submodule update`  
`git pull`

**Восстановить журнал к исходному виду (в случае удаления каких-либо файлов):**  
``

#### Работа с тематическими выпусками

**Документация:**  
https://git-scm.com/docs/git-submodule  
https://git-scm.com/docs/gitmodules  
https://git-scm.com/book/en/v2/Git-Tools-Submodules (see NOTE)

Есть возможность изменить репозиторий выпуска (после `git submodule init`):  
`git config submodule.SUBMODULE_NAME.url PRIVATE_URL`

**Скачать все выпуски:**  
`git submodule update --init` либо `git clone --recursive Git_URL`.

**Синхронизировать все выпуски:**  
`git submodule update`

**Записать локальные изменения на удалённый репозиторий:**
```
cd dokuwiki/data/media/pub/[выпуск]
git checkout master
git pull
git add .
git config user.name "Admin"
git config user.email rusidea.info@yandex.ru
git commit -m "vX.X.X"
git push -u origin master
```

**Удалить выпуск:**  
```
git rm --cached dokuwiki/data/media/pub/[выпуск]
rm -rf .git/modules/dokuwiki/data/media/pub/[выпуск]
```
```
vim .gitmodules
vim .git/config
```
```
git commit -m "Выпуск удалён"
```
Если необходимо удалить все файлы выпуска локально, то:  
`rm -rf dokuwiki/data/media/pub/[выпуск]`

Если[^](http://blogs.atlassian.com/2013/03/git-submodules-workflows-tips/) редактировать `.gitmodules`, то необходимо синхронизировать с `.git/config`:  
`git submodule sync`

**Создать выпуск:**  
```
mkdir dokuwiki/data/media/pub/[выпуск]
cd dokuwiki/data/media/pub/[выпуск]
git init
git remote add origin Git_URL
git add .
git commit -m "Тематический выпуск аналитики"
git push -u origin master
```
```
git submodule add Git_URL dokuwiki/data/media/pub/[выпуск]
```
или
```
git submodule add ../analitika_[выпуск].git dokuwiki/data/media/pub/[выпуск]
```

**Добавить выпуск:**
```
git submodule add Git_URL dokuwiki/data/media/pub/[выпуск]
```
или
```
git submodule add ../analitika_[выпуск].git dokuwiki/data/media/pub/[выпуск]
```

#### Подготовка и публикация официальной версии журнала

**Редактировать историю изменений в выпусках:**  
https://git-scm.com/book/be/v2/Git-Tools-Rewriting-History

**Опубликовать выпуски**

**Клонировать репозиторий Git локально без инициализации модулей**

**Обновить ветку master:**
```
git checkout master  
git pull
```

**Установить контактные данные редактора:**
```
git config user.name "Admin"  
git config user.email rusidea.info@yandex.ru
```

**Заблокировать ветку master на запись изменений:**  
?

**Записать изменения ветки master в ветку release:**  
```
git checkout release  
git branch -vv  
git merge --squash master
```

**Записать все изменения в один коммит в стандартном виде:**
```
git commit -m "vX.X.X"
```

**Произвести завершающее редактирование:**  
```
vim dokuwiki/data/pages/pub.txt  
vim dokuwiki/data/pages/index.txt
```

**Добавить отредактированные файлы:**  
```
git add dokuwiki/data/pages/pub.txt  
git add dokuwiki/data/pages/index.txt
```

**Записать все изменения в один коммит в стандартном виде:**  
```
git commit -m "Журнал vX.X.X готов к публикации"
```

**Произвести слияние ветки public с веткой release:**  
```
git checkout public  
git branch -vv  
git merge --squash release
```

**Записать все изменения в один коммит в стандартном виде:**  
```
git commit -m 'Полная версия vX.X.X журнала "Аналитика"'
```

**Проверить историю изменений ветки public:**  
```
git log --pretty=oneline -5
```

**Произвести слияние ветки release с веткой public:**  
```
git checkout release  
git branch -vv  
git merge public
```

**Произвести слияние ветки master с веткой release:**  
```
git checkout master  
git branch -vv  
git merge release
```

**Опубликовать ветку public в репозитории на github.com:**  
```
git checkout public
git remote add github https://github.com/rusidea/analitika.git
git push github public:master
```

**Произвести слияние ветки webserver с веткой release и подготовить к записи на сервер:**  
```
git checkout webserver  
git branch -vv  
git revert 'hash of the last patch commit'  
git merge public  
git log --pretty=oneline -5  
git am webserver.patch  
```
или
```
vim webserver.patch  
git apply --check webserver.patch  
git apply webserver.patch  
git commit -m "Изменения для публикации на вебсервере"
```
*Для подготовки файла изменений:*  
```
git format-patch master (or any commit) --stdout > webserver.patch
```

**Записать журнал на веб-сервер:**  
```
cd dokuwiki; lftp -c "open -u login analitika.rusidea.info; mirror -c -R -e   ./ ./"
```

**Полезные ссылки:**  
https://ariejan.net/2009/10/26/how-to-create-and-apply-a-patch-with-git/  
https://git-scm.com/docs/git-format-patch  
https://git-scm.com/docs/git-apply  
https://git-scm.com/docs/git-am  
http://stackoverflow.com/questions/6658313/generate-a-git-patch-for-a-specific-commit  
http://stackoverflow.com/questions/4114095/revert-git-repo-to-a-previous-commit  
http://stackoverflow.com/questions/927358/how-do-you-undo-the-last-commit  
http://stackoverflow.com/questions/5189560/squash-my-last-x-commits-together-using-git  
https://help.github.com/articles/pushing-to-a-remote/

### Рабочие заметки

Для синхронизации на сайт:  
`cd dokuwiki; lftp -c "open -u login analitika.rusidea.info; mirror -c -R -e   ./ ./"`

Для синхронизации с сайта:  
`cd dokuwiki; lftp -c "open -u login analitika.rusidea.info; mirror -c -e   ./ ./"`

Возможно стоит посмотреть на инструкции для работы с Git по адресу:  
https://www.mediawiki.org/wiki/Download_from_Git/ru

Мы используем аналогичную систему доставки кода.

https://about.gitlab.com/2015/02/17/gitlab-annex-solves-the-problem-of-versioning-large-binaries-with-git/
