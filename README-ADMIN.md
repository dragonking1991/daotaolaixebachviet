# HƯỚNG DẪN TRANG QUẢN TRỊ (ADMIN)

## Mục lục
1. [Tổng quan](#1-tổng-quan)
2. [Đăng nhập & Bảo mật](#2-đăng-nhập--bảo-mật)
3. [Hệ thống phân quyền](#3-hệ-thống-phân-quyền)
4. [Quản lý người dùng](#4-quản-lý-người-dùng)
5. [Quản lý sản phẩm](#5-quản-lý-sản-phẩm)
6. [Quản lý tin tức / bài viết](#6-quản-lý-tin-tức--bài-viết)
7. [Quản lý trang tĩnh](#7-quản-lý-trang-tĩnh)
8. [Quản lý liên hệ](#8-quản-lý-liên-hệ)
9. [Quản lý đơn hàng](#9-quản-lý-đơn-hàng)
10. [Quản lý newsletter](#10-quản-lý-newsletter)
11. [Quản lý tags](#11-quản-lý-tags)
12. [Quản lý hình ảnh & media](#12-quản-lý-hình-ảnh--media)
13. [Quản lý SEO](#13-quản-lý-seo)
14. [Cài đặt website](#14-cài-đặt-website)
15. [Quản lý địa điểm](#15-quản-lý-địa-điểm)
16. [Import / Export](#16-import--export)
17. [Quản lý cache](#17-quản-lý-cache)
18. [Quản lý ngôn ngữ](#18-quản-lý-ngôn-ngữ)
19. [Push Notification](#19-push-notification)
20. [AJAX Endpoints (Admin)](#20-ajax-endpoints-admin)
21. [Cấu trúc file](#21-cấu-trúc-file)
22. [Bảng cơ sở dữ liệu](#22-bảng-cơ-sở-dữ-liệu)

---

## 1. Tổng quan

Đây là hệ thống CMS (Content Management System) tùy chỉnh được xây dựng bằng PHP thuần, **không phải WordPress**. Phiên bản 7.0.0.

### Cách hoạt động của Admin

- **Điểm vào**: `admin/index.php`
- **Routing**: Dựa trên query string với 2 tham số chính:
  - `com` — Tên module (tương ứng file `admin/sources/{com}.php`)
  - `act` — Hành động trong module (`man`/`add`/`edit`/`save`/`delete`)
- **Tham số phụ**: `type` (loại), `kind` (phân loại con), `id` (mã bản ghi), `p` (phân trang)

**Ví dụ URL:**
```
admin/index.php?com=product&act=man&type=san-pham     → Danh sách sản phẩm
admin/index.php?com=news&act=add&type=tin-tuc          → Thêm bài viết mới
admin/index.php?com=user&act=edit_admin&id=1            → Sửa tài khoản admin
```

### Luồng xử lý

```
Request → admin/index.php
  → Kiểm tra đăng nhập (session)
  → Kiểm tra phân quyền
  → Load file: admin/sources/{$com}.php
  → Xử lý theo switch($act)
  → Load template: admin/templates/{$template}_tpl.php
  → Hiển thị giao diện
```

---

## 2. Đăng nhập & Bảo mật

### Đăng nhập

- **URL**: `admin/index.php?com=user&act=login`
- **AJAX xử lý**: `admin/ajax/ajax_login.php`
- **Mật khẩu mặc định**: `admin123` (cấu hình trong `libraries/config.php`)

### Mã hóa mật khẩu

Sử dụng `md5(secret + password + salt)`:
- `secret` = `$@tyutgt`
- `salt` = `swKJjeS!t`
- Hàm: `Functions::encrypt_password()`

### Bảo vệ brute-force

| Cài đặt | Giá trị |
|---------|---------|
| Số lần thử tối đa | 5 lần |
| Thời gian khóa | 15 phút |
| Bảng lưu trữ | `table_user_limit` (theo IP) |

### Xác thực phiên (Session)

- Session kiểm tra trên **mỗi request**
- `login_session` = `md5(sha1(password + username))`
- Phiên hết hạn sau **24 giờ** không hoạt động
- Phát hiện đăng nhập đồng thời → cảnh báo "Có người đang đăng nhập tài khoản của bạn"

### Đăng xuất

- URL: `admin/index.php?com=user&act=logout`
- Xóa toàn bộ session

---

## 3. Hệ thống phân quyền

> **Lưu ý**: Hiện tại đang **tắt** (`$config['permission'] = false`). Bật trong `config.php` để sử dụng.

### Cách hoạt động

| Bảng | Mô tả |
|------|-------|
| `table_permission_group` | Nhóm quyền (vai trò) |
| `table_permission` | Quyền cụ thể cho từng nhóm |
| `table_user.id_nhomquyen` | Liên kết user với nhóm quyền |

### Định dạng quyền

Quyền được lưu dạng chuỗi: `{com}_{act}_{type}`

**Ví dụ:**
- `product_man_san-pham` — Xem danh sách sản phẩm
- `news_man_cat_tin-tuc` — Xem danh mục tin tức
- `setting_man` — Quản lý cài đặt

### Quản lý nhóm quyền

| URL | Chức năng |
|-----|-----------|
| `?com=user&act=permission_group` | Danh sách nhóm quyền |
| `?com=user&act=add_permission_group` | Thêm nhóm quyền mới |
| `?com=user&act=edit_permission_group&id=X` | Sửa nhóm quyền |
| `?com=user&act=delete_permission_group&id=X` | Xóa nhóm quyền |

### Bỏ qua phân quyền

Tài khoản `admin` và chế độ `debug-developer = true` bỏ qua mọi kiểm tra phân quyền.

---

## 4. Quản lý người dùng

**File**: `admin/sources/user.php`
**Bảng**: `table_user`, `table_user_log`, `table_user_limit`

### Quản lý tài khoản Admin

| URL | Chức năng |
|-----|-----------|
| `?com=user&act=man_admin` | Danh sách tài khoản admin |
| `?com=user&act=add_admin` | Thêm admin mới |
| `?com=user&act=edit_admin&id=X` | Sửa thông tin admin |
| `?com=user&act=save_admin` | Lưu thông tin admin |
| `?com=user&act=delete_admin&id=X` | Xóa tài khoản admin |
| `?com=user&act=admin_edit` | Admin tự sửa thông tin cá nhân |

### Quản lý thành viên (Member)

| URL | Chức năng |
|-----|-----------|
| `?com=user&act=man` | Danh sách thành viên |
| `?com=user&act=add` | Thêm thành viên mới |
| `?com=user&act=edit&id=X` | Sửa thông tin thành viên |
| `?com=user&act=save` | Lưu thông tin thành viên |
| `?com=user&act=delete&id=X` | Xóa thành viên |

---

## 5. Quản lý sản phẩm

**File**: `admin/sources/product.php`
**Bảng**: `table_product`, `table_product_list`, `table_product_cat`, `table_product_item`, `table_product_sub`, `table_product_brand`, `table_product_mau`, `table_product_size`, `table_gallery`, `table_seo`

### Cấu trúc danh mục 4 cấp

```
Danh mục cấp 1 (product_list)
  └── Danh mục cấp 2 (product_cat)
        └── Danh mục cấp 3 (product_item)
              └── Danh mục cấp 4 (product_sub)
                    └── Sản phẩm (product)
```

### Quản lý sản phẩm

| URL | Chức năng |
|-----|-----------|
| `?com=product&act=man&type=san-pham` | Danh sách sản phẩm |
| `?com=product&act=add&type=san-pham` | Thêm sản phẩm mới |
| `?com=product&act=edit&type=san-pham&id=X` | Sửa sản phẩm |
| `?com=product&act=copy&type=san-pham&id=X` | Nhân bản sản phẩm |
| `?com=product&act=save&type=san-pham` | Lưu sản phẩm |
| `?com=product&act=delete&type=san-pham&id=X` | Xóa sản phẩm |

### Quản lý danh mục

| Cấp | URL (act) | Prefix |
|-----|-----------|--------|
| Cấp 1 | `man_list` / `add_list` / `edit_list` / `save_list` / `delete_list` | `_list` |
| Cấp 2 | `man_cat` / `add_cat` / `edit_cat` / `save_cat` / `delete_cat` | `_cat` |
| Cấp 3 | `man_item` / `add_item` / `edit_item` / `save_item` / `delete_item` | `_item` |
| Cấp 4 | `man_sub` / `add_sub` / `edit_sub` / `save_sub` / `delete_sub` | `_sub` |

### Quản lý thuộc tính sản phẩm

| Thuộc tính | URL (act) | Bảng |
|------------|-----------|------|
| Thương hiệu | `man_brand` / `add_brand` / `edit_brand` / `save_brand` / `delete_brand` | `table_product_brand` |
| Màu sắc | `man_mau` / `add_mau` / `edit_mau` / `save_mau` / `delete_mau` | `table_product_mau` |
| Kích cỡ | `man_size` / `add_size` / `edit_size` / `save_size` / `delete_size` | `table_product_size` |

### Quản lý ảnh sản phẩm (Gallery)

| URL | Chức năng |
|-----|-----------|
| `?com=product&act=man_photo&type=san-pham&id=X` | Danh sách ảnh |
| `?com=product&act=add_photo&type=san-pham&id=X` | Thêm ảnh |
| `?com=product&act=edit_photo&type=san-pham&id=X` | Sửa ảnh |
| `?com=product&act=delete_photo&type=san-pham&id=X` | Xóa ảnh |

### Thông tin sản phẩm

| Trường | Mô tả |
|--------|-------|
| `tenvi` | Tên sản phẩm (tiếng Việt) |
| `tenkhongdauvi` | Slug URL |
| `photo` | Ảnh đại diện |
| `gia` | Giá gốc |
| `giamoi` | Giá khuyến mãi |
| `giakm` | Phần trăm giảm giá |
| `mota` | Mô tả ngắn |
| `noidung` | Nội dung chi tiết (CKEditor) |
| `noibat` | Sản phẩm nổi bật |
| `hienthi` | Hiển thị/ẩn |
| `stt` | Thứ tự sắp xếp |

---

## 6. Quản lý tin tức / bài viết

**File**: `admin/sources/news.php`
**Bảng**: `table_news`, `table_news_list`, `table_news_cat`, `table_news_item`, `table_news_sub`, `table_gallery`, `table_seo`

### Cấu trúc giống sản phẩm — 4 cấp danh mục

Các loại tin tức được phân biệt bởi `type`:

| Type | Mô tả |
|------|-------|
| `tin-tuc` | Tin tức chung |
| `dao-tao` | Chương trình đào tạo |
| `chieu-sinh-dao-tao` | Chiêu sinh đào tạo |
| `tai-lieu` | Tài liệu |
| `chinh-sach` | Chính sách |

### CRUD bài viết

| URL | Chức năng |
|-----|-----------|
| `?com=news&act=man&type=tin-tuc` | Danh sách bài viết |
| `?com=news&act=add&type=tin-tuc` | Thêm bài viết |
| `?com=news&act=edit&type=tin-tuc&id=X` | Sửa bài viết |
| `?com=news&act=save&type=tin-tuc` | Lưu bài viết |
| `?com=news&act=delete&type=tin-tuc&id=X` | Xóa bài viết |

Danh mục cũng tương tự sản phẩm: `man_list`, `man_cat`, `man_item`, `man_sub` + CRUD cho từng cấp.

---

## 7. Quản lý trang tĩnh

**File**: `admin/sources/static.php`
**Bảng**: `table_static`, `table_seo`

Trang tĩnh là các trang có nội dung cố định (Giới thiệu, Liên hệ...).

| URL | Chức năng |
|-----|-----------|
| `?com=static&act=man&type=gioi-thieu` | Danh sách trang tĩnh |
| `?com=static&act=edit&type=gioi-thieu&id=X` | Sửa nội dung trang |
| `?com=static&act=save&type=gioi-thieu` | Lưu trang |

> Trang tĩnh thường chỉ có **1 bản ghi** mỗi loại, không có thêm/xóa.

---

## 8. Quản lý liên hệ

**File**: `admin/sources/contact.php`
**Bảng**: `table_contact`

Hiển thị và quản lý các form liên hệ từ khách hàng.

| URL | Chức năng |
|-----|-----------|
| `?com=contact&act=man&type=lien-he` | Danh sách liên hệ |
| `?com=contact&act=edit&type=lien-he&id=X` | Xem chi tiết (đánh dấu đã đọc) |
| `?com=contact&act=delete&type=lien-he&id=X` | Xóa liên hệ |

---

## 9. Quản lý đơn hàng

**File**: `admin/sources/order.php`
**Bảng**: `table_order`, `table_order_detail`, `table_status`

> **Lưu ý**: Cần bật `$config['order']['active'] = true` trong `config.php`.

### Chức năng

| URL | Chức năng |
|-----|-----------|
| `?com=order&act=man` | Danh sách đơn hàng |
| `?com=order&act=edit&id=X` | Chi tiết đơn hàng |
| `?com=order&act=save` | Cập nhật trạng thái |
| `?com=order&act=delete&id=X` | Xóa đơn hàng |

### Bộ lọc đơn hàng

- Theo trạng thái (mới, xác nhận, đã giao, hủy)
- Theo phương thức thanh toán
- Theo khoảng thời gian
- Theo khoảng giá
- Theo địa chỉ (tỉnh/quận/phường)

### Xuất đơn hàng

- **Excel**: `?com=excel&act=man&id=X` → Xuất file `.xlsx`
- **Word**: `?com=word&act=man&id=X` → Xuất file `.docx`

---

## 10. Quản lý Newsletter

**File**: `admin/sources/newsletter.php`
**Bảng**: `table_newsletter`

| URL | Chức năng |
|-----|-----------|
| `?com=newsletter&act=man` | Danh sách người đăng ký |
| `?com=newsletter&act=add` | Thêm người đăng ký |
| `?com=newsletter&act=edit&id=X` | Sửa thông tin |
| `?com=newsletter&act=delete&id=X` | Xóa |

### Gửi email hàng loạt

Tính năng gửi email marketing cho toàn bộ danh sách đăng ký thông qua PHPMailer với template HTML.

---

## 11. Quản lý Tags

**File**: `admin/sources/tags.php`
**Bảng**: `table_tags`, `table_seo`

| URL | Chức năng |
|-----|-----------|
| `?com=tags&act=man` | Danh sách tags |
| `?com=tags&act=add` | Thêm tag mới |
| `?com=tags&act=edit&id=X` | Sửa tag |
| `?com=tags&act=save` | Lưu tag |
| `?com=tags&act=delete&id=X` | Xóa tag |

Mỗi tag có: tên, slug, hình ảnh, SEO riêng.

---

## 12. Quản lý hình ảnh & Media

**File**: `admin/sources/photo.php`
**Bảng**: `table_photo`, `table_gallery`

### 2 chế độ

#### Ảnh tĩnh (`photo_static`)
Quản lý ảnh cố định: banner, logo, watermark, icon mạng xã hội...

| URL | Chức năng |
|-----|-----------|
| `?com=photo&act=man&type=slider` | Danh sách slider |
| `?com=photo&act=edit&type=slider&id=X` | Sửa ảnh |
| `?com=photo&act=save&type=slider` | Lưu ảnh |

#### Album ảnh (`man_photo`)
Quản lý album ảnh với nhiều hình.

| URL | Chức năng |
|-----|-----------|
| `?com=photo&act=man_photo&type=album` | Danh sách album |
| `?com=photo&act=add_photo&type=album` | Thêm album |
| `?com=photo&act=edit_photo&type=album&id=X` | Sửa album |

### Watermark

- Xem trước watermark: xem cách watermark hiển thị trên ảnh
- Cài đặt watermark: upload ảnh watermark, cấu hình vị trí

---

## 13. Quản lý SEO

**File**: `admin/sources/seopage.php`
**Bảng**: `table_seopage`

Cài đặt SEO riêng cho từng trang danh sách.

| URL | Chức năng |
|-----|-----------|
| `?com=seopage&act=man` | Danh sách trang SEO |
| `?com=seopage&act=edit&id=X` | Sửa SEO trang |
| `?com=seopage&act=save` | Lưu SEO |

### Thông tin SEO mỗi bản ghi

Mỗi sản phẩm/bài viết/tag đều có SEO riêng (bảng `table_seo`):
- Meta title
- Meta description
- Meta keywords
- OG image
- H1 tag

---

## 14. Cài đặt Website

**File**: `admin/sources/setting.php`
**Bảng**: `table_setting`, `table_seo`

| URL | Chức năng |
|-----|-----------|
| `?com=setting&act=man` | Trang cài đặt |
| `?com=setting&act=save` | Lưu cài đặt |

### Thông tin cài đặt

- Tên website
- Thông tin liên hệ (địa chỉ, điện thoại, email, fax)
- Liên kết mạng xã hội
- SEO trang chủ
- Cấu hình JSON (options) cho các tùy chọn nâng cao

---

## 15. Quản lý địa điểm

**File**: `admin/sources/places.php`
**Bảng**: `table_city`, `table_district`, `table_wards`, `table_street`

> Cần bật `$config['places']['active'] = true`.

Quản lý cấu trúc địa chỉ phân cấp:

```
Tỉnh/Thành phố (city)
  └── Quận/Huyện (district)
        └── Phường/Xã (wards)
              └── Đường/Phố (street)
```

CRUD đầy đủ cho từng cấp.

---

## 16. Import / Export

### Import sản phẩm

**File**: `admin/sources/import.php` | **Bảng**: `table_excel`

- Upload file Excel (.xlsx)
- Upload ảnh hàng loạt
- Tạo sản phẩm tự động từ dữ liệu Excel

### Export sản phẩm

**File**: `admin/sources/export.php`

- Xuất danh sách sản phẩm ra file Excel (.xlsx)
- Bao gồm toàn bộ trường dữ liệu

### Export đơn hàng

- **Excel** (`admin/sources/excel.php`): Xuất đơn hàng ra `.xlsx`
- **Word** (`admin/sources/word.php`): Xuất đơn hàng ra `.docx` (sử dụng PHPWord)

---

## 17. Quản lý Cache

**File**: `admin/sources/cache.php`

| URL | Chức năng |
|-----|-----------|
| `?com=cache&act=delete` | Xóa toàn bộ cache |

Cache dạng file lưu trong thư mục `caches/`. Mỗi khi cập nhật dữ liệu, nên xóa cache để hiển thị dữ liệu mới nhất.

---

## 18. Quản lý ngôn ngữ

**File**: `admin/sources/lang.php` | **Bảng**: `table_lang`

> Chỉ hoạt động khi `debug-developer = true`.

| URL | Chức năng |
|-----|-----------|
| `?com=lang&act=man` | Danh sách chuỗi ngôn ngữ |
| `?com=lang&act=add` | Thêm chuỗi mới |
| `?com=lang&act=edit&id=X` | Sửa chuỗi |
| `?com=lang&act=delete&id=X` | Xóa chuỗi |

Tạo file ngôn ngữ PHP từ cơ sở dữ liệu (`libraries/lang/langvi.php`).

---

## 19. Push Notification

**File**: `admin/sources/pushOnesignal.php` | **Bảng**: `table_pushonesignal`

> Cần bật `$config['onesignal'] = true`.

Gửi thông báo đẩy qua OneSignal API. CRUD + đồng bộ thông báo.

---

## 20. AJAX Endpoints (Admin)

| File | Chức năng | Xác thực |
|------|-----------|----------|
| `admin/ajax/ajax_login.php` | Xử lý đăng nhập | Không (trước khi đăng nhập) |
| `admin/ajax/ajax_config.php` | Khởi tạo môi trường cho AJAX | Có |
| `admin/ajax/ajax_status.php` | Bật/tắt hiển thị bản ghi | Có |
| `admin/ajax/ajax_stt.php` | Cập nhật thứ tự sắp xếp | Có |
| `admin/ajax/ajax_slug.php` | Kiểm tra slug trùng lặp | Có |
| `admin/ajax/ajax_upload.php` | Upload ảnh gallery | Có |
| `admin/ajax/ajax_copy.php` | Nhân bản sản phẩm/bài viết | Có |
| `admin/ajax/ajax_category.php` | Dropdown danh mục liên tầng | Có |
| `admin/ajax/ajax_filer.php` | Quản lý gallery (sắp xếp, đổi tên) | Có |
| `admin/ajax/ajax_place.php` | Dropdown địa điểm liên tầng | Có |

---

## 21. Cấu trúc file

```
admin/
├── index.php                    ← Điểm vào admin
├── ajax/
│   ├── ajax_config.php          ← Khởi tạo AJAX
│   ├── ajax_login.php           ← Xử lý đăng nhập
│   ├── ajax_status.php          ← Bật/tắt hiển thị
│   ├── ajax_stt.php             ← Cập nhật thứ tự
│   ├── ajax_slug.php            ← Kiểm tra slug
│   ├── ajax_upload.php          ← Upload ảnh
│   ├── ajax_copy.php            ← Nhân bản bản ghi
│   ├── ajax_category.php        ← Dropdown danh mục
│   ├── ajax_filer.php           ← Quản lý gallery
│   └── ajax_place.php           ← Dropdown địa điểm
├── sources/
│   ├── user.php                 ← Quản lý người dùng & phân quyền
│   ├── product.php              ← Quản lý sản phẩm
│   ├── news.php                 ← Quản lý tin tức
│   ├── static.php               ← Trang tĩnh
│   ├── contact.php              ← Liên hệ
│   ├── order.php                ← Đơn hàng
│   ├── newsletter.php           ← Newsletter
│   ├── tags.php                 ← Tags
│   ├── photo.php                ← Hình ảnh
│   ├── gallery.php              ← Gallery (module dùng chung)
│   ├── setting.php              ← Cài đặt
│   ├── seopage.php              ← SEO trang
│   ├── places.php               ← Địa điểm
│   ├── cache.php                ← Quản lý cache
│   ├── lang.php                 ← Ngôn ngữ
│   ├── import.php               ← Import sản phẩm
│   ├── export.php               ← Export sản phẩm
│   ├── excel.php                ← Export đơn hàng Excel
│   ├── word.php                 ← Export đơn hàng Word
│   └── pushOnesignal.php        ← Push notification
├── templates/                   ← Giao diện admin
├── assets/                      ← CSS/JS/Images admin
├── ckeditor/                    ← Trình soạn thảo WYSIWYG
└── elfinder/                    ← Quản lý file trực quan
```

---

## 22. Bảng cơ sở dữ liệu

| Bảng | Mô tả | Module sử dụng |
|------|-------|-----------------|
| `table_user` | Tài khoản admin | user |
| `table_user_log` | Nhật ký đăng nhập | user |
| `table_user_limit` | Giới hạn đăng nhập (brute-force) | user |
| `table_permission_group` | Nhóm quyền | user |
| `table_permission` | Quyền cụ thể | user |
| `table_member` | Thành viên frontend | user |
| `table_product` | Sản phẩm | product |
| `table_product_list` | Danh mục sản phẩm cấp 1 | product |
| `table_product_cat` | Danh mục sản phẩm cấp 2 | product |
| `table_product_item` | Danh mục sản phẩm cấp 3 | product |
| `table_product_sub` | Danh mục sản phẩm cấp 4 | product |
| `table_product_brand` | Thương hiệu | product |
| `table_product_mau` | Màu sắc | product |
| `table_product_size` | Kích cỡ | product |
| `table_news` | Bài viết / Tin tức | news |
| `table_news_list` | Danh mục tin tức cấp 1 | news |
| `table_news_cat` | Danh mục tin tức cấp 2 | news |
| `table_news_item` | Danh mục tin tức cấp 3 | news |
| `table_news_sub` | Danh mục tin tức cấp 4 | news |
| `table_static` | Trang tĩnh | static |
| `table_tags` | Tags | tags |
| `table_gallery` | Gallery ảnh (dùng chung) | product, news, photo |
| `table_photo` | Ảnh/banner/slider | photo |
| `table_contact` | Liên hệ | contact |
| `table_newsletter` | Đăng ký nhận tin / đơn hàng | newsletter, order |
| `table_order` | Đơn hàng | order |
| `table_order_detail` | Chi tiết đơn hàng | order |
| `table_status` | Trạng thái đơn hàng | order |
| `table_setting` | Cài đặt website | setting |
| `table_seo` | SEO metadata | product, news, tags, static |
| `table_seopage` | SEO trang danh sách | seopage |
| `table_lang` | Chuỗi ngôn ngữ | lang |
| `table_excel` | Import sản phẩm | import |
| `table_city` | Tỉnh/Thành phố | places, order |
| `table_district` | Quận/Huyện | places, order |
| `table_wards` | Phường/Xã | places, order |
| `table_street` | Đường/Phố | places |
| `table_counter` | Thống kê lượt truy cập | statistic |
| `table_user_online` | Người dùng online | statistic |
| `table_pushonesignal` | Push notification | pushOnesignal |
