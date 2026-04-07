# HƯỚNG DẪN TRANG FRONTEND (TRANG CÔNG KHAI)

## Mục lục
1. [Tổng quan kiến trúc](#1-tổng-quan-kiến-trúc)
2. [Bản đồ URL Routing](#2-bản-đồ-url-routing)
3. [Trang chủ](#3-trang-chủ)
4. [Tin tức / Bài viết](#4-tin-tức--bài-viết)
5. [Sản phẩm](#5-sản-phẩm)
6. [Trang tĩnh (Giới thiệu)](#6-trang-tĩnh-giới-thiệu)
7. [Liên hệ / Đăng ký](#7-liên-hệ--đăng-ký)
8. [Tra cứu thông tin](#8-tra-cứu-thông-tin)
9. [Tìm kiếm](#9-tìm-kiếm)
10. [Hệ thống thành viên](#10-hệ-thống-thành-viên)
11. [Giỏ hàng & Đặt hàng](#11-giỏ-hàng--đặt-hàng)
12. [Tags](#12-tags)
13. [Video & Thư viện ảnh](#13-video--thư-viện-ảnh)
14. [Hệ thống SEO](#14-hệ-thống-seo)
15. [Hệ thống Template](#15-hệ-thống-template)
16. [AJAX Endpoints](#16-ajax-endpoints)
17. [Hệ thống Cache](#17-hệ-thống-cache)
18. [Thumbnail & Watermark](#18-thumbnail--watermark)
19. [Thống kê truy cập](#19-thống-kê-truy-cập)
20. [Tính năng khác](#20-tính-năng-khác)
21. [Cấu trúc file](#21-cấu-trúc-file)
22. [Bảng cơ sở dữ liệu](#22-bảng-cơ-sở-dữ-liệu)

---

## 1. Tổng quan kiến trúc

Website Đào tạo Lái xe Bách Việt được xây dựng bằng PHP thuần trên framework CMS tùy chỉnh v7.0.0.

### Điểm vào: `index.php`

```
Request từ trình duyệt
  → index.php
    → session_start()
    → Load config (libraries/config.php)
    → Khởi tạo các service:
        PDODb, Seo, Email, AltoRouter, FileCache,
        Functions, BreadCrumbs, Statistic, Cart,
        MobileDetect, AddonsOnline, CssMinify, JsMinify
    → Load router (libraries/router.php)
        → Khớp URL với route
        → Phân tích slug từ database
        → Load source file (sources/{module}.php)
        → Load dữ liệu chung (sources/allpage.php)
    → Render template (templates/index.php)
```

### Autoloader

Lớp `AutoLoad` tự động load class từ `libraries/class/class.{TênLớp}.php`.

### Các service chính

| Service | Mô tả |
|---------|-------|
| `PDODb` | Kết nối database, query builder, prepared statements |
| `AltoRouter` | Hệ thống routing URL |
| `Seo` | Quản lý metadata SEO |
| `Email` | Gửi email qua PHPMailer |
| `FileCache` | Cache truy vấn vào file |
| `Functions` | Xác thực, upload, ảnh, slug, phân trang, XSS |
| `Cart` | Giỏ hàng (session-based) |
| `BreadCrumbs` | Tạo breadcrumb + JSON-LD |
| `Statistic` | Thống kê lượt truy cập |
| `MobileDetect` | Phát hiện thiết bị di động |
| `CssMinify` / `JsMinify` | Gộp và nén CSS/JS |
| `AddonsOnline` | Lazy-load widget bên ngoài |

---

## 2. Bản đồ URL Routing

Routing sử dụng `AltoRouter`, được cấu hình trong `libraries/router.php`.

### Routes cố định

| URL | Phương thức | Trang | Mô tả |
|-----|-------------|-------|-------|
| `/` | GET/POST | Trang chủ | Homepage |
| `/index.php` | GET/POST | Trang chủ | Homepage (alias) |
| `/sitemap.xml` | GET/POST | Sitemap | XML Sitemap |
| `/admin` hoặc `/admin/` | GET | Redirect | Chuyển hướng đến `admin/index.php` |

### Routes động (Slug-based)

| URL Pattern | Mô tả |
|-------------|-------|
| `/{slug}` | **Catch-all** — Phân tích slug từ database để xác định trang |
| `/{slug}/{lang}/` | Phiên bản ngôn ngữ của trang |
| `/account/{action}` | Trang tài khoản thành viên |

### Routes hình ảnh

| URL Pattern | Mô tả |
|-------------|-------|
| `/thumbs/{w}x{h}x{z}/{src}` | Tạo thumbnail theo kích thước |
| `/watermark/product/{w}x{h}x{z}/{src}` | Ảnh sản phẩm có watermark |
| `/watermark/news/{w}x{h}x{z}/{src}` | Ảnh tin tức có watermark |

### Hệ thống phân tích Slug ("Requick")

Khi người dùng truy cập URL dạng `/{slug}`, hệ thống thực hiện 2 bước:

**Bước 1 — Tìm slug trong database:**

Mảng `$requick` (file `libraries/router.php`) định nghĩa các bảng cần tìm:

| Bảng | Type | URL ví dụ |
|------|------|-----------|
| `news_list` | `chieu-sinh-dao-tao` | `/hang-a1`, `/hang-b-so-tu-dong` |
| `news` | `tin-tuc` | `/ten-bai-viet-tin-tuc` |
| `news` | `dao-tao` | `/ten-khoa-dao-tao` |
| `news` | `tai-lieu` | `/ten-tai-lieu` |
| `news` | `chinh-sach` | `/ten-chinh-sach` |
| `static` | `gioi-thieu` | `/gioi-thieu` |
| `static` | `lien-he` | `/lien-he` |

Hệ thống query cột `tenkhongdauvi` (slug tiếng Việt) để tìm bản ghi phù hợp.

**Bước 2 — Dispatch đến source file:**

| Giá trị `$com` | File source | Template | Mô tả |
|-----------------|-------------|----------|-------|
| `index` | `sources/index.php` | `index/index` | Trang chủ |
| `tra-cuu-thong-tin` | `sources/tracuu.php` | `tracuu/tracuu` | Tra cứu học viên |
| `lien-he` | `sources/contact.php` | `contact/contact` | Form liên hệ |
| `gioi-thieu` | `sources/static.php` | `static/static` | Trang giới thiệu |
| `tin-tuc` | `sources/news.php` | `news/news` hoặc `news/news_detail` | Tin tức |
| `dao-tao` | `sources/news.php` | `news/news` hoặc `news/news_detail` | Đào tạo |
| `chieu-sinh-dao-tao` | `sources/news.php` | `news/news` hoặc `news/news_detail` | Chiêu sinh |
| `tai-lieu` | `sources/news.php` | `news/news` hoặc `news/news_detail` | Tài liệu |
| `chinh-sach` | `sources/news.php` | `news/news_detail` | Chính sách |
| `tim-kiem` | `sources/search.php` | `news/news` | Tìm kiếm |
| `ngon-ngu` | — | — | Chuyển đổi ngôn ngữ |
| `sitemap` | `libraries/sitemap.php` | — | XML sitemap |

---

## 3. Trang chủ

**File**: `sources/index.php`
**Template**: `templates/index/index_tpl.php`

### Nội dung hiển thị

1. **Slider ảnh** — Carousel hình ảnh (từ `table_photo`, type `slider`, kích thước 1366x500)
2. **Bài viết nổi bật** — Các bài viết đào tạo có `noibat > 0`
3. **Cảm nhận học viên** — Từ bài viết loại "cảm nhận"
4. **Tin tức mới nhất** — Từ `table_news`
5. **Video** — Từ `table_photo` type video
6. **Giới thiệu** — Nội dung tóm tắt

### Dữ liệu chung cho mọi trang (allpage.php)

File `sources/allpage.php` được load cho **tất cả trang**, cung cấp:
- Logo website (từ `table_photo`)
- Số lượt truy cập (qua `Statistic`)
- Xử lý form đăng ký nhận tin (newsletter)

---

## 4. Tin tức / Bài viết

**File**: `sources/news.php`
**Bảng**: `table_news`, `table_news_list`, `table_news_cat`, `table_news_item`, `table_news_sub`

### Cấu trúc danh mục 4 cấp

```
Danh mục cấp 1 (news_list)
  └── Danh mục cấp 2 (news_cat)
        └── Danh mục cấp 3 (news_item)
              └── Danh mục cấp 4 (news_sub)
                    └── Bài viết (news)
```

### Cách hoạt động

Hệ thống xác định loại trang dựa trên tham số:

| Tham số | Hiển thị | Template |
|---------|----------|----------|
| `id` (có) | Chi tiết bài viết | `news/news_detail_tpl.php` |
| `idl` (có) | Danh sách theo danh mục cấp 1 | `news/news_tpl.php` |
| `idc` (có) | Danh sách theo danh mục cấp 2 | `news/news_tpl.php` |
| `idi` (có) | Danh sách theo danh mục cấp 3 | `news/news_tpl.php` |
| `ids` (có) | Danh sách theo danh mục cấp 4 | `news/news_tpl.php` |
| Không có | Tất cả bài viết của loại | `news/news_tpl.php` |

### Trang chi tiết bài viết

- Hiển thị nội dung đầy đủ
- Tăng lượt xem (`luotxem`)
- Gallery ảnh (từ `table_gallery`)
- Breadcrumb phân cấp
- Bài viết liên quan
- Chia sẻ mạng xã hội
- SEO metadata (Open Graph, Twitter Cards)

### Các loại tin tức

| Type | URL | Mô tả |
|------|-----|-------|
| `tin-tuc` | `/tin-tuc` | Tin tức chung |
| `dao-tao` | `/dao-tao` | Chương trình đào tạo |
| `chieu-sinh-dao-tao` | `/chieu-sinh-dao-tao` | Chiêu sinh |
| `tai-lieu` | `/tai-lieu` | Tài liệu học |
| `chinh-sach` | `/chinh-sach` | Chính sách |

---

## 5. Sản phẩm

**File**: `sources/product.php`
**Bảng**: `table_product`, `table_product_list/cat/item/sub`, `table_product_brand`, `table_product_mau`, `table_product_size`, `table_gallery`, `table_tags`

### Cấu trúc danh mục 4 cấp (giống tin tức)

### Thông tin sản phẩm

| Trường | Mô tả |
|--------|-------|
| `tenvi` | Tên sản phẩm |
| `tenkhongdauvi` | Slug URL |
| `photo` | Ảnh đại diện |
| `gia` | Giá gốc |
| `giamoi` | Giá khuyến mãi |
| `giakm` | Phần trăm giảm giá |
| `mota` | Mô tả ngắn |
| `noidung` | Nội dung chi tiết |
| `luotxem` | Lượt xem |
| `noibat` | Nổi bật |

### Tính năng

- Lọc theo thương hiệu, màu sắc, kích cỡ
- Gallery ảnh sản phẩm
- Tags sản phẩm (sử dụng `find_in_set()`)
- Xem nhanh (Quick View) qua AJAX modal
- Thêm vào yêu thích (session-based)
- Thêm vào giỏ hàng
- Phân trang AJAX

---

## 6. Trang tĩnh (Giới thiệu)

**File**: `sources/static.php`
**Bảng**: `table_static`

Lấy 1 bản ghi từ `table_static` theo `type` (ví dụ: `gioi-thieu`).

Hiển thị nội dung HTML đầy đủ từ CKEditor.

---

## 7. Liên hệ / Đăng ký

**File**: `sources/contact.php`
**Bảng**: `table_newsletter`

### Form liên hệ

Các trường nhập liệu:
- Họ tên
- Số điện thoại
- Địa chỉ
- Email
- Tiêu đề
- Nội dung
- File đính kèm

### Luồng xử lý

```
1. Người dùng submit form
2. Xác thực Google reCAPTCHA v3 (điểm >= 0.5)
3. Sanitize dữ liệu với htmlspecialchars()
4. Upload file đính kèm (nếu có)
5. Lưu vào bảng table_newsletter (type = 'lien-he')
6. Tạo email HTML với inline styles
7. Gửi email thông báo cho admin
8. Gửi email xác nhận cho khách hàng
9. Chuyển hướng với thông báo thành công
```

### Form đăng ký nhận tin (Newsletter)

Xử lý trong `sources/allpage.php`:
- Form đăng ký nhanh trên mọi trang
- Cũng sử dụng reCAPTCHA
- Lưu vào `table_newsletter`
- Gửi email xác nhận

---

## 8. Tra cứu thông tin

**File**: `sources/tracuu.php`
**AJAX**: `ajax/tracuu.php`

### Chức năng

Cho phép học viên tra cứu thông tin bằng CCCD (Căn cước công dân).

### Các loại tra cứu

| Loại | Type | Mô tả |
|------|------|-------|
| Giấy phép lái xe | `gplx` | Tra cứu số GPLX |
| Giấy xác nhận | `gxn` | Tra cứu giấy xác nhận |
| QR thanh toán | `qr` | Tra cứu QR thanh toán |

### Cách hoạt động

1. Người dùng nhập số CCCD
2. AJAX gửi đến `ajax/tracuu.php`
3. Query bảng `table_product` theo CCCD cho từng loại
4. Trả về kết quả hiển thị trên trang

---

## 9. Tìm kiếm

**File**: `sources/search.php`
**Template**: `templates/news/news_tpl.php`

### Cách hoạt động

1. Nhận từ khóa từ `$_GET['key']`
2. Tìm trong bảng `table_news` qua các loại: `dao-tao`, `chieu-sinh-dao-tao`, `tin-tuc`, `tai-lieu`
3. So khớp theo tên (`tenvi`) hoặc slug (`tenkhongdauvi`)
4. Hỗ trợ tìm kiếm không dấu: thay `đ` bằng `d`
5. Kết quả phân trang
6. Hiển thị dạng danh sách tin tức

---

## 10. Hệ thống thành viên

**File**: `sources/user.php`
**Bảng**: `table_member`

### Trang tài khoản

| URL | Chức năng |
|-----|-----------|
| `/account/dang-nhap` | Đăng nhập |
| `/account/dang-ky` | Đăng ký tài khoản |
| `/account/quen-mat-khau` | Quên mật khẩu |
| `/account/kich-hoat` | Kích hoạt tài khoản |
| `/account/thong-tin` | Thông tin cá nhân (yêu cầu đăng nhập) |
| `/account/dang-xuat` | Đăng xuất |

### Xác thực

- Mật khẩu: MD5 hash
- Session: `$_SESSION[LoginMember]` chứa `{active, id, username, dienthoai, email, ten, login_session}`
- Cookie "Ghi nhớ": `login_member_id` + `login_member_session`
- Kiểm tra đăng nhập: `Functions::checkLogin()` chạy trên mỗi request
- Giới hạn 1 thiết bị đăng nhập (single-device enforcement)

### Chức năng

- **Đăng nhập**: username + password → kiểm tra `table_member`
- **Đăng ký**: tạo tài khoản mới
- **Quên mật khẩu**: gửi email đặt lại mật khẩu
- **Kích hoạt**: xác thực email
- **Cập nhật**: thay đổi thông tin, đổi mật khẩu (cần nhập mật khẩu cũ)

---

## 11. Giỏ hàng & Đặt hàng

### Giỏ hàng

**Class**: `libraries/class/class.Cart.php`
**AJAX**: `ajax/ajax_cart.php`

Giỏ hàng dựa trên **session** (`$_SESSION['cart']`).

#### Cấu trúc item giỏ hàng

```php
[
    'productid' => ID sản phẩm,
    'qty'       => Số lượng,
    'mau'       => Màu sắc,
    'size'      => Kích cỡ,
    'code'      => md5(pid + color + size)  // mã duy nhất
]
```

#### Thao tác AJAX

| Lệnh (`cmd`) | Chức năng |
|---------------|-----------|
| `add-cart` | Thêm sản phẩm vào giỏ, trả về số lượng mới |
| `update-cart` | Cập nhật số lượng, trả về giá mới |
| `delete-cart` | Xóa sản phẩm, trả về tổng mới |
| `ship-cart` | Tính phí vận chuyển theo phường/xã |

### Đặt hàng

**File**: `sources/order.php`
**Bảng**: `table_newsletter`, `table_order`, `table_order_detail`

#### Luồng đặt hàng

```
1. Chọn sản phẩm → Thêm vào giỏ
2. Vào trang thanh toán
3. Nhập thông tin:
   - Họ tên, email, số điện thoại
   - Địa chỉ: Tỉnh → Quận → Phường (dropdown liên tầng AJAX)
4. Chọn phương thức thanh toán (từ table_news type 'hinh-thuc-thanh-toan')
5. Tính phí vận chuyển (từ table_wards.gia)
6. Tạo mã đơn hàng (6 ký tự ngẫu nhiên)
7. Lưu đơn hàng vào table_newsletter
8. Gửi email xác nhận cho admin và khách hàng
9. Hiển thị thông báo đặt hàng thành công
```

---

## 12. Tags

**File**: `sources/tags.php`
**Bảng**: `table_tags`

- URL: `/{slug-cua-tag}`
- Hiển thị danh sách sản phẩm hoặc bài viết được gắn tag
- Sử dụng `find_in_set()` để lọc
- Mỗi tag có SEO riêng

---

## 13. Video & Thư viện ảnh

### Video

**File**: `sources/video.php`
**Bảng**: `table_photo`

- Hiển thị danh sách video YouTube
- Phân trang
- Nhúng video từ YouTube embed

### Thư viện ảnh

**File**: `sources/thuvien.php`
**Bảng**: `table_photo`

- Hiển thị album ảnh
- Lightbox xem ảnh (Fancybox3)

---

## 14. Hệ thống SEO

### Class: `libraries/class/class.Seo.php`

#### Quản lý SEO metadata

| Phương thức | Mô tả |
|-------------|-------|
| `setSeo($key, $value)` | Đặt giá trị SEO (title, keywords, description, url, photo...) |
| `getSeo($key)` | Lấy giá trị SEO |
| `getSeoDB($id, $com, $act, $type)` | Load SEO từ bảng `table_seo` |
| `updateSeoDB()` | Cập nhật kích thước ảnh vào database |

#### Luồng SEO cho mỗi trang

```
1. Source file load SEO từ table_seo cho nội dung cụ thể
2. Nếu không có SEO tùy chỉnh → lấy tiêu đề nội dung
3. Kích thước ảnh tự động phát hiện qua getImgSize()
4. Cache kích thước ảnh trong cột options (JSON)
5. Template head.php xuất ra:
   - <title>
   - Meta keywords, description
   - Open Graph (og:title, og:description, og:image, og:url, og:type)
   - Twitter Cards
   - Canonical URL
   - Favicon
6. Template seo.php xuất:
   - H-card microformat (ẩn)
   - H1 tag ngữ nghĩa
```

### Breadcrumbs

**Class**: `libraries/class/class.BreadCrumbs.php`

- Tạo breadcrumb phân cấp với Bootstrap markup
- Xuất JSON-LD `BreadcrumbList` structured data cho Google
- Ví dụ: `Trang chủ > Đào tạo > Hạng B > Bài viết chi tiết`

### Sitemap

**File**: `libraries/sitemap.php`

- Tạo sitemap XML động
- Lặp qua tất cả slug từ mảng `$requick`
- URL chuẩn cho Google Search Console

---

## 15. Hệ thống Template

### Layout chính: `templates/index.php`

```
<!DOCTYPE html>
├── <head>
│   ├── layout/head.php       ← Meta tags, favicon, viewport
│   └── layout/css.php        ← CSS bundle, Google Analytics
├── <body>
│   ├── layout/seo.php        ← H-card ẩn, H1 SEO
│   ├── layout/header.php     ← Thanh thông tin, logo, menu
│   │   ├── layout/menu.php   ← Menu desktop
│   │   └── layout/mmenu.php  ← Menu mobile (hamburger)
│   ├── NẾU trang chủ:
│   │   └── layout/slider_slick.php  ← Slider carousel
│   ├── NGƯỢC LẠI:
│   │   └── layout/breadcrumb.php    ← Breadcrumb điều hướng
│   ├── {$template}_tpl.php   ← NỘI DUNG TRANG (thay đổi theo trang)
│   ├── layout/footer.php     ← Thông tin công ty, liên kết, fanpage
│   ├── layout/copy.php       ← Copyright
│   ├── layout/addon.php      ← Nút gọi điện, Zalo, CTA đăng ký
│   ├── layout/modal.php      ← Popup modals
│   └── layout/js.php         ← jQuery, Bootstrap JS, Slick, reCAPTCHA
```

### Thư mục template theo trang

| Thư mục | Template | Trang |
|---------|----------|-------|
| `templates/index/` | `index_tpl.php` | Trang chủ |
| `templates/news/` | `news_tpl.php`, `news_detail_tpl.php` | Tin tức |
| `templates/contact/` | `contact_tpl.php` | Liên hệ |
| `templates/static/` | `static_tpl.php` | Giới thiệu |
| `templates/tracuu/` | `tracuu_tpl.php` | Tra cứu |
| `templates/account/` | `dangnhap_tpl.php`, `dangky_tpl.php`, `quenmatkhau_tpl.php`, `kichhoat_tpl.php`, `thongtin_tpl.php` | Tài khoản |
| `templates/order/` | `order_tpl.php` | Thanh toán |
| `templates/product/` | `product_tpl.php`, `product_detail_tpl.php` | Sản phẩm |
| `templates/album/` | `album_tpl.php` | Album ảnh |
| `templates/video/` | `video_tpl.php` | Video |

### Layout partials

| File | Mô tả |
|------|-------|
| `layout/head.php` | Meta tags, Open Graph, Twitter Cards, canonical |
| `layout/css.php` | CSS bundle (CssMinify) + Google Analytics |
| `layout/seo.php` | H-card ẩn + H1 SEO |
| `layout/header.php` | Thanh trên (địa chỉ, SĐT, tra cứu) + logo + menu |
| `layout/menu.php` | Menu desktop dropdown |
| `layout/mmenu.php` | Menu mobile hamburger |
| `layout/slider_slick.php` | Slider trang chủ (Slick Carousel) |
| `layout/breadcrumb.php` | Breadcrumb điều hướng |
| `layout/footer.php` | Footer: thông tin, mạng xã hội, chính sách, fanpage |
| `layout/copy.php` | Thanh copyright |
| `layout/addon.php` | Nút nổi: gọi điện, Zalo, Messenger, CTA đăng ký |
| `layout/modal.php` | Popup modals |
| `layout/js.php` | JavaScript loading + reCAPTCHA + OneSignal |
| `layout/gioithieu.php` | Section giới thiệu (trang chủ) |
| `layout/tintuc.php` | Section tin mới nhất |
| `layout/video.php` | Section video |
| `layout/left.php` | Sidebar menu bên trái |
| `layout/share.php` | Nút chia sẻ mạng xã hội |
| `layout/thongke.php` | Hiển thị thống kê truy cập |
| `layout/transfer.php` | Trang chuyển hướng / thông báo flash |

---

## 16. AJAX Endpoints

Tất cả file AJAX đều include `ajax/ajax_config.php` để khởi tạo môi trường (session, DB, Functions, Cache, Cart, lang).

| File | Phương thức | Chức năng |
|------|-------------|-----------|
| `ajax/ajax_cart.php` | POST | Thao tác giỏ hàng: thêm, cập nhật, xóa, tính ship |
| `ajax/ajax_product.php` | GET | Phân trang sản phẩm AJAX |
| `ajax/ajax_addons.php` | GET | Lazy-load widget: video, bản đồ, fanpage, Messenger |
| `ajax/ajax_video.php` | POST | Load embed video YouTube theo ID |
| `ajax/ajax_district.php` | POST | Load dropdown quận/huyện theo tỉnh |
| `ajax/ajax_wards.php` | POST | Load dropdown phường/xã theo quận |
| `ajax/ajax_street.php` | POST | Load dropdown đường/phố |
| `ajax/ajax_xemnhanh.php` | POST | Modal xem nhanh sản phẩm (đầy đủ thông tin) |
| `ajax/ajax_yeuthich.php` | POST | Thêm sản phẩm vào danh sách yêu thích (session) |
| `ajax/ajax_color.php` | POST | Load ảnh sản phẩm theo màu |
| `ajax/ajax_phantrang.php` | POST | Phân trang AJAX tổng quát |
| `ajax/ajax_run_slick.php` | GET | Khởi tạo Slick Carousel qua AJAX |
| `ajax/ajax_run_slick_cap2.php` | GET | Khởi tạo Slick Carousel danh mục con |
| `ajax/tracuu.php` | POST | Tra cứu học viên theo CCCD |

---

## 17. Hệ thống Cache

**Class**: `libraries/class/class.FileCache.php`
**Thư mục**: `caches/`

### Cách hoạt động

```
1. Khi cần query database:
   cache_key = MD5(câu SQL)

2. Kiểm tra file cache tồn tại?
   → CÓ: kiểm tra TTL hết hạn chưa?
     → CHƯA: trả về dữ liệu từ cache
     → RỒI: xóa file, query DB, lưu cache mới
   → KHÔNG: query DB, lưu cache mới

3. Dữ liệu cache = serialize(data) + timestamp hết hạn
```

### Phương thức chính

| Phương thức | Mô tả |
|-------------|-------|
| `getCache($sql, $type, $ttl)` | Lấy từ cache hoặc query DB |
| `store($key, $data, $ttl)` | Lưu dữ liệu vào cache file |
| `fetch($key)` | Đọc dữ liệu từ cache |
| `DeleteCache()` | Xóa toàn bộ cache |

### TTL thường dùng

| Dữ liệu | TTL |
|----------|-----|
| Cài đặt website (`table_setting`) | 7200 giây (2 giờ) |
| Danh sách bài viết | 7200 giây (2 giờ) |
| Thống kê truy cập | 1800 giây (30 phút) |

> **Lưu ý**: Sau khi cập nhật dữ liệu trong admin, nên xóa cache (`?com=cache&act=delete`) để thấy thay đổi ngay.

---

## 18. Thumbnail & Watermark

### Thumbnail — Tạo ảnh theo kích thước

**URL format**: `/thumbs/{rộng}x{cao}x{chất_lượng}/{đường_dẫn_ảnh}`

**Ví dụ**: `thumbs/400x300x1/upload/news/image.jpg`

| Tham số | Mô tả |
|---------|-------|
| `{rộng}` | Chiều rộng (px) |
| `{cao}` | Chiều cao (px) |
| `{chất_lượng}` | `1` = thường, `2` = cao |
| `{đường_dẫn_ảnh}` | Đường dẫn ảnh gốc |

**Cơ chế**:
1. Lần đầu truy cập → `Functions::createThumb()` tạo ảnh mới
2. Lưu cache vào thư mục `thumbs/` dưới dạng file tĩnh
3. Lần sau truy cập → Apache trả file tĩnh trực tiếp (không qua PHP)

### Watermark — Chèn watermark lên ảnh

**URL format**: `/watermark/{loại}/{rộng}x{cao}x{chất_lượng}/{đường_dẫn_ảnh}`

| Loại | Mô tả |
|------|-------|
| `product` | Watermark ảnh sản phẩm |
| `news` | Watermark ảnh tin tức |

Cấu hình watermark trong admin: `?com=photo&act=man&type=watermark`

---

## 19. Thống kê truy cập

**Class**: `libraries/class/class.Statistic.php`
**Bảng**: `table_counter`, `table_user_online`

### Lượt truy cập

- Theo dõi IP khách (khóa 15 phút để tránh đếm trùng)
- Thống kê: hôm nay, hôm qua, tuần, tháng, tổng
- Hiển thị ở footer qua `layout/thongke.php`

### Đang online

- Theo dõi session hoạt động trong `table_user_online`
- Hết hạn sau 10 phút không hoạt động
- Hiển thị số người đang online

---

## 20. Tính năng khác

### CSS/JS Minification

- `CssMinify` và `JsMinify` gộp nhiều file CSS/JS thành 1
- Chế độ debug (`debug-css`/`debug-js` trong config): `true` = load file riêng lẻ, `false` = file gộp

### Chống SQL Injection

**Class**: `libraries/class/class.AntiSQLInjection.php`

- Kiểm tra query string cho các pattern tấn công phổ biến
- Trả 404 nếu phát hiện
- Bảo vệ bổ sung — bảo vệ chính là PDO prepared statements

### Phát hiện thiết bị

- `MobileDetect` phát hiện mobile/tablet
- Hiện tại cả desktop và mobile dùng chung template `templates/`
- Template mobile `m/` đã bị comment (chưa sử dụng)

### Đa ngôn ngữ

- Hạ tầng sẵn cho Việt-Anh (`vi`/`en`)
- **Hiện đang khóa cứng**: `$_SESSION['lang'] = 'vi';`
- Cột database có hậu tố theo ngôn ngữ: `tenvi`, `tenvien`, `tenkhongdauvi`, `tenkhongdauen`
- Hằng ngôn ngữ trong `libraries/lang/langvi.php`

### Addons lazy-load

**Class**: `libraries/class/class.AddonsOnline.php`

Lazy-load widget ngoài khi cuộn trang (giảm thời gian load):
- Facebook fanpage
- Facebook Messenger
- Google Maps
- Video

### Push Notification

- Tích hợp OneSignal (hiện `active: false`)
- Khi bật: gửi thông báo đẩy đến người dùng đã đăng ký

---

## 21. Cấu trúc file

```
/ (root)
├── index.php                     ← Điểm vào chính
├── 404.php                       ← Trang lỗi 404
├── lock.php                      ← Trang bảo trì
├── robots.txt                    ← Cấu hình bot
├── .htaccess                     ← Rewrite rules, bảo mật, cache
│
├── libraries/                    ← Thư viện core
│   ├── config.php                ← Cấu hình database, website, API
│   ├── autoload.php              ← Autoloader cho class
│   ├── router.php                ← Định tuyến URL + dispatch
│   ├── constant.php              ← Tạo thư mục upload tự động
│   ├── requick.php               ← Phân tích slug → module
│   ├── sitemap.php               ← XML Sitemap generator
│   ├── checkSSL.php              ← Kiểm tra & redirect SSL
│   ├── wejnswpwhitespacefix.php  ← Sửa lỗi whitespace
│   ├── class/                    ← Tất cả class PHP
│   │   ├── class.PDODb.php       ← Database wrapper
│   │   ├── class.Functions.php   ← Hàm tiện ích chung
│   │   ├── class.Seo.php         ← Quản lý SEO
│   │   ├── class.Email.php       ← Gửi email
│   │   ├── class.FileCache.php   ← Cache file
│   │   ├── class.Cart.php        ← Giỏ hàng
│   │   ├── class.BreadCrumbs.php ← Breadcrumb
│   │   ├── class.Statistic.php   ← Thống kê
│   │   ├── class.AltoRouter.php  ← URL Router
│   │   ├── class.MobileDetect.php← Phát hiện thiết bị
│   │   ├── class.CssMinify.php   ← Gộp CSS
│   │   ├── class.JsMinify.php    ← Gộp JS
│   │   ├── class.AntiSQLInjection.php ← Chống SQL injection
│   │   ├── class.PaginationsAjax.php  ← Phân trang AJAX
│   │   └── class.AddonsOnline.php     ← Lazy-load addons
│   ├── lang/                     ← File ngôn ngữ
│   │   └── langvi.php            ← Tiếng Việt
│   ├── PHPMailer/                ← Thư viện gửi email
│   ├── PHPExcel/                 ← Thư viện Excel
│   └── PHPWord/                  ← Thư viện Word
│
├── sources/                      ← Logic xử lý từng trang
│   ├── allpage.php               ← Dữ liệu chung cho mọi trang
│   ├── index.php                 ← Trang chủ
│   ├── news.php                  ← Tin tức / Bài viết
│   ├── product.php               ← Sản phẩm
│   ├── static.php                ← Trang tĩnh
│   ├── contact.php               ← Liên hệ
│   ├── tracuu.php                ← Tra cứu học viên
│   ├── search.php                ← Tìm kiếm
│   ├── user.php                  ← Hệ thống thành viên
│   ├── order.php                 ← Đặt hàng
│   ├── tags.php                  ← Tags
│   ├── video.php                 ← Video
│   └── thuvien.php               ← Thư viện ảnh
│
├── templates/                    ← Giao diện HTML
│   ├── index.php                 ← Layout chính (master template)
│   ├── layout/                   ← Partials (header, footer, menu...)
│   ├── index/                    ← Template trang chủ
│   ├── news/                     ← Template tin tức
│   ├── product/                  ← Template sản phẩm
│   ├── contact/                  ← Template liên hệ
│   ├── static/                   ← Template trang tĩnh
│   ├── tracuu/                   ← Template tra cứu
│   ├── account/                  ← Template tài khoản
│   ├── order/                    ← Template đặt hàng
│   ├── album/                    ← Template album ảnh
│   └── video/                    ← Template video
│
├── ajax/                         ← AJAX handlers
│   ├── ajax_config.php           ← Khởi tạo AJAX
│   ├── ajax_cart.php             ← Giỏ hàng
│   ├── ajax_product.php          ← Phân trang sản phẩm
│   ├── ajax_district.php         ← Dropdown quận/huyện
│   ├── ajax_wards.php            ← Dropdown phường/xã
│   ├── ajax_street.php           ← Dropdown đường/phố
│   ├── ajax_xemnhanh.php         ← Xem nhanh sản phẩm
│   ├── ajax_yeuthich.php         ← Yêu thích
│   ├── ajax_color.php            ← Ảnh theo màu
│   ├── ajax_phantrang.php        ← Phân trang tổng quát
│   ├── ajax_video.php            ← Load video
│   ├── ajax_addons.php           ← Lazy-load addons
│   ├── ajax_run_slick.php        ← Slick Carousel
│   ├── ajax_run_slick_cap2.php   ← Slick danh mục con
│   └── tracuu.php                ← Tra cứu học viên
│
├── assets/                       ← CSS, JS, fonts, images
│   ├── bootstrap/                ← Bootstrap framework
│   ├── css/                      ← CSS tùy chỉnh (style.css, media.css)
│   ├── js/                       ← JavaScript (jquery, apps.js)
│   ├── slick/                    ← Slick Carousel
│   ├── fancybox3/                ← Fancybox lightbox
│   ├── fotorama/                 ← Fotorama gallery
│   ├── owlcarousel2/             ← Owl Carousel
│   ├── fontawesome512/           ← Font Awesome icons
│   ├── mmenu/                    ← Mobile menu
│   └── ...                       ← Các thư viện khác
│
├── upload/                       ← Thư mục upload (auto-created)
│   ├── photo/                    ← Ảnh tĩnh
│   ├── news/                     ← Ảnh tin tức
│   ├── product/                  ← Ảnh sản phẩm
│   ├── file/                     ← File đính kèm
│   └── ...
│
├── caches/                       ← File cache
├── thumbs/                       ← Thumbnail cache
├── watermark/                    ← Watermark cache
└── logs/                         ← Log files
```

---

## 22. Bảng cơ sở dữ liệu

> Prefix bảng: `table_` (cấu hình trong `config.php`, code sử dụng `#_` sẽ tự động thay thế)

| Bảng | Mô tả | Trang sử dụng |
|------|-------|---------------|
| `table_setting` | Cài đặt website (JSON options) | Mọi trang |
| `table_seo` | SEO metadata cho từng bản ghi | Mọi trang nội dung |
| `table_seopage` | SEO cho trang danh sách | Trang danh sách |
| `table_photo` | Logo, slider, icon, video, watermark | Trang chủ, video |
| `table_gallery` | Gallery ảnh (dùng chung) | Sản phẩm, tin tức |
| `table_news` | Bài viết / tin tức | Tin tức, đào tạo, tài liệu |
| `table_news_list` | Danh mục tin tức cấp 1 | Tin tức |
| `table_news_cat` | Danh mục tin tức cấp 2 | Tin tức |
| `table_news_item` | Danh mục tin tức cấp 3 | Tin tức |
| `table_news_sub` | Danh mục tin tức cấp 4 | Tin tức |
| `table_product` | Sản phẩm (+ tra cứu học viên) | Sản phẩm, tra cứu |
| `table_product_list` | Danh mục sản phẩm cấp 1 | Sản phẩm |
| `table_product_cat` | Danh mục sản phẩm cấp 2 | Sản phẩm |
| `table_product_item` | Danh mục sản phẩm cấp 3 | Sản phẩm |
| `table_product_sub` | Danh mục sản phẩm cấp 4 | Sản phẩm |
| `table_product_brand` | Thương hiệu | Sản phẩm |
| `table_product_mau` | Màu sắc | Sản phẩm |
| `table_product_size` | Kích cỡ | Sản phẩm |
| `table_tags` | Tags | Tags |
| `table_static` | Trang tĩnh (giới thiệu...) | Trang tĩnh |
| `table_newsletter` | Liên hệ, đăng ký, đơn hàng | Liên hệ, đặt hàng |
| `table_member` | Thành viên đăng ký | Tài khoản |
| `table_order` | Đơn hàng | Đặt hàng |
| `table_order_detail` | Chi tiết đơn hàng | Đặt hàng |
| `table_status` | Trạng thái đơn hàng | Đặt hàng |
| `table_counter` | Đếm lượt truy cập | Thống kê |
| `table_user_online` | Phiên online | Thống kê |
| `table_city` | Tỉnh/Thành phố | Đặt hàng, liên hệ |
| `table_district` | Quận/Huyện | Đặt hàng, liên hệ |
| `table_wards` | Phường/Xã (+ phí ship) | Đặt hàng |
| `table_lang` | Chuỗi ngôn ngữ | Đa ngôn ngữ |
