# 1C-Bitrix Module
Модуль для интеграции платежной системы Click Узбекистан для 1C Bitrix

1. Перейдите на папку кодировки который запущен ваша версия Битрикса (UTF-8 или WIN-1251)
2. Скопируйте папку click.uzbekistan в папку bitrix/modules/
3. После этого зайдите в админку Битрикса и перейдите в 
   **Marketplace => Установленные расширения**
   
   В списке доступных расширений должен появится Click Uzbekistan, установите его.
   
4. Перейдите в раздел **Магазин => Платежные системы**. Нажмите на кнопку **Добавить платежную систему**.
5. В форме добавления новой платежной системы выберите обработчик - Click Uzbekistan.
6. Внизу заполните поля полученнымы данными от CLICK  

   Номер поставщика (MERCHANT_ID)   
   Номер пользователя поставщика (MERCHANT_USER_ID)   
   Номер сервиса (SERVICE_ID)   
   Секретный ключ (SECRET_KEY)
   
7. Для проведения платежей необходимо прописать адреса проверки и результата в кабинете. Для этого:
   1. Авторизуйтесь в кабинете merchant.click.uz
   2. Перейдите в категорию "Сервисы" (слева в кабинете)
   3. Нажмите на иконку карандаша в поле "Действие" (в крайней правой ячейке таблицы)
   4. Пропишите адрес проверки и адрес результата