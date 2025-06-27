# Laravel Approval Flow

Laravel Approval Flowは、Laravelアプリケーションに承認フローと段階的な承認プロセスを追加するためのパッケージです。

## 特徴

- **段階的承認プロセス**: 複数のステップからなる承認フローを設定可能
- **Livewire統合**: リアルタイムUIコンポーネントでの承認管理
- **通知システム**: 承認リクエストと状態変更の自動通知
- **柔軟な設定**: カスタマイズ可能な承認ルールとワークフロー
- **モデル統合**: Eloquentモデルとの簡単な統合

## インストール

Composerを使ってインストールしてください:

```bash
composer require lastdino/approval-flow
```

下記のコマンドで必要なマイグレーションファイルの出力とマイグレーションを実行します:

```bash
php artisan vendor:publish --tag="approvalflow-migrations"
php artisan migrate
```

Configファイルは下記のコマンドで出力可能です:

```bash
php artisan vendor:publish --tag="approvalflow-config"
```

出力されたConfigファイルの中身は次のような感じです:

```php
return [
    /**
     * This is the name of the table that contains the roles used to classify users
     * (for spatie-laravel-permissions it is the `roles` table
     */
    'roles_model' => "\\Spatie\\Permission\\Models\\Role",

    /**
     * The model associated with login and authentication
     */
    'users_model' => "\\App\\Models\\User",

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => 'flow',
        'middleware' => ['web'],
        'guards' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Date and Time Configuration
    |--------------------------------------------------------------------------
    */
    'datetime' => [
        'formats' => [
            'default' => 'Y-m-d H:i:s',
            'date' => 'Y-m-d',
            'time' => 'H:i:s',
            'year_month' => 'Y-m',
        ],
    ],

    /**
     * User Display Configuration
     */
    'user' => [
        'display_name_column' => 'Full_name',
        'fallback_columns' => ['full_name', 'display_name','name'],
    ],
];
```

オプションとして次のコマンドを実行するとViewファイルも出力可能です:

```bash
php artisan vendor:publish --tag="approvalflow-views"
```

言語ファイルを出力する場合は以下のコマンドを実行してください:

```bash
php artisan vendor:publish --tag="approvalflow-lang"
```

CSSアセットファイルを出力する場合は以下のコマンドを実行してください:

```bash
php artisan vendor:publish --tag="approvalflow-assets"
```

## 基本的な使用方法

### 1. Livewireレイアウトの設定

使用しているLivewireレイアウトファイルの`<body>`タグの上部に以下のスタックディレクティブを追加してください：

```blade
<!-- resources/views/layouts/app.blade.php または他のレイアウトファイル -->
@stack('approval-flow')
<body>
    <!-- 残りのレイアウト内容 -->
</body>
```

### 2. モデルの設定

承認フローを使用したいモデルに必要なトレイトを追加します：

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lastdino\ApprovalFlow\Traits\HasApprovalFlow;

class Document extends Model
{
    use HasApprovalFlow;

    // モデルの実装
}
```

### 3. 承認フロータスクの登録

`registerApprovalFlowTask`メソッドを使用して承認フロータスクを登録します：

```php
// モデルインスタンス（承認対象）
$document = Document::find(1);

// 承認フロータスクを登録
$task = $document->registerApprovalFlowTask(
    flowId: 1,              // 使用する承認フローのID
    authorId: Auth::id(),   // 申請者のユーザーID
    comment: '承認お願いします', // 任意のコメント
    systemRoles: [1, 2] // 承認者のロールid（オプション）
);

```

`registerApprovalFlowTask`メソッドは、以下のパラメータを受け取ります：

- `flowId`: 使用する承認フローのID
- `authorId`: 申請者のユーザーID
- `comment`: 申請時のコメント（オプション）
- `systemRoles`: 承認者のロールid配列（オプション）

承認フロータスクが作成されると、設定されたフローデータに基づいて自動的に処理が開始されます。

### 申請のキャンセル

申請者は、承認プロセスが完了する前に申請をキャンセルすることができます。キャンセルは、`cancelApprovalFlowTask`メソッドを使用して行います：

```php
// タスクをキャンセルする
$document->cancelApprovalFlowTask(
    userId: Auth::id(),      // キャンセルを実行するユーザーのID（通常は申請者自身）
    comment: 'キャンセルの理由' // キャンセル理由（オプション）
);
```

注意事項：
- キャンセルは、タスクが完了（承認または拒否）される前にのみ実行できます
- デフォルトでは、申請者本人のみがキャンセル可能です（ただし、管理者権限を持つユーザーにも許可するように設定可能）
- キャンセルされたタスクは、履歴として保存されます

### 4. 承認時と拒否時の振る舞いのカスタマイズ

デフォルトでは、モデルが承認されたとき・拒否されたときに`status`フィールドが更新されますが、これらの振る舞いはモデル側でオーバーライドできます：

```php
// モデルクラス内でメソッドをオーバーライド
class Document extends Model
{
    use HasApprovalFlow;

    /**
     * 承認時の振る舞いをカスタマイズ
     */
    public function onApproved(): void
    {
        // デフォルトの振る舞い
        $this->update(['status' => 'approved']);

        // 追加の処理例
        event(new DocumentApproved($this));
        Mail::to($this->author->email)->send(new DocumentApprovedMail($this));
    }

    /**
     * 拒否時の振る舞いをカスタマイズ
     */
    public function onRejected(): void
    {
        // デフォルトの振る舞い
        $this->update(['status' => 'rejected']);

        // 追加の処理例
        event(new DocumentRejected($this));
        Mail::to($this->author->email)->send(new DocumentRejectedMail($this));
    }
}
```

### 5. ルート設定

パッケージは自動的に `/flow` プレフィックスでルートを登録します。設定ファイルでカスタマイズ可能です。

デフォルトで使用可能な主要なルート：

- `/flow/task_list` - 承認タスク一覧の表示
- `/flow/flow_list` - 承認フロー一覧の表示
- `/flow/edit/{id}` - 承認フローの編集
- `/flow/detail/{id}` - タスクの詳細表示

## 設定

### 必要な依存関係

このパッケージは以下のパッケージと連携します：

- **jerosoler/Drawflow**: フロー図作成ライブラリ（フローエディタに使用）
- **Laravel**: フレームワーク基盤 (`illuminate/support`)
- **Laravel Livewire**: リアルタイムUI (`livewire/livewire`)
- **Flux**: UIコンポーネントライブラリ (`livewire/flux`)
- **ロール管理システム**: `Spatie Laravel Permission`または独自のロール管理システム

### ユーザーモデルの設定

設定ファイルでユーザーモデルとロールモデルを指定してください。`roles_model`には`Spatie Laravel Permission`のRoleモデルでも、独自のロールモデルでも指定可能です：

```php
'users_model' => "\\App\\Models\\User",
'roles_model' => "\\Spatie\\Permission\\Models\\Role", // または独自のロールモデル
```

独自のロールモデルを使用する場合、そのモデルには少なくとも`id`と`name`プロパティが必要です。

### 表示名の設定

ユーザーの表示名に使用するカラムを設定できます：

```php
'user' => [
    'display_name_column' => 'Full_name',
    'fallback_columns' => ['full_name', 'display_name', 'name'],
],
```

### フロントエンド要件

フローエディタは`jerosoler/Drawflow`を使用します。ビューファイルをpublishすると、自動的に必要なCDNリンクが含まれます：

```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/jerosoler/Drawflow/dist/drawflow.min.css">
<script src="https://cdn.jsdelivr.net/gh/jerosoler/Drawflow/dist/drawflow.min.js"></script>
```

また、アセットファイルを公開するとCSSも追加されます：

```bash
php artisan vendor:publish --tag="approvalflow-assets"
```

アセットの変更を反映するには、必ず以下のコマンドを実行してアセットをビルドしてください：

```bash
npm run build
```

または開発中は以下のコマンドを使用できます：

```bash
npm run dev
```

これにより、フロー編集画面で必要なCSSとJavaScriptが正しく読み込まれます。

## 貢献

バグ報告や機能リクエストは、GitHubのIssuesでお願いします。

プルリクエストも歓迎します：

1. フォークしてください
2. フィーチャーブランチを作成してください (`git checkout -b feature/amazing-feature`)
3. 変更をコミットしてください (`git commit -m 'Add amazing feature'`)
4. ブランチにプッシュしてください (`git push origin feature/amazing-feature`)
5. プルリクエストを開いてください

## ライセンス

このパッケージは[MIT License](LICENSE)の下で公開されています。

## サポート

質問やサポートが必要な場合は、以下の方法でお問い合わせください：

- GitHub Issues
- Email: support@example.com

## 変更履歴

詳細な変更履歴は[CHANGELOG.md](CHANGELOG.md)をご確認ください。
