# qf_app_builder
qianfan AppBuilder openApi sdk wrapper

百度千帆 AppBuilder openApi sdk wrapper

## 安装
```php
composer require jeeinn/qf-app-builder
```

## 示例
```php
require_once __DIR__ . '/vendor/autoload.php';
use Jeeinn\QfAppBuilder\Agent;
use Jeeinn\QfAppBuilder\Utils;
$appId = 'your app id';
$appToken = 'your app token';

// 创建会话
$agent = new Agent($appId, $appToken);
$conversationId = $agent->newConversation();
echo Utils::formatMsg("conversation_id created, conversation_id: {$conversationId}");

// 上传文件
$fileId = $agent->uploadFile(__DIR__ . '/your_test_file.xlsx', $conversationId);
echo Utils::formatMsg("file uploaded, file id: {$fileId}");

// 对话
$query = "我该如何描述和总结表格中的数据？";
$answer = $agent->talk($conversationId, $query, $fileId);
echo Utils::formatMsg("answer: {$answer}");

```

## 测试用例

请先修改 `tests/AgentTest.php` 中的 $appId 和 $appToken 参数

```bash
./vendor/bin/phpunit --filter testComplete tests/
```

#### 单元测试
```php
PHPUnit 9.6.19 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)[2024-04-30 11:43:42] conversation_id created, conversation_id: 19a40e06-1729-42e1-a039-a37bbcdf80a5
[2024-04-30 11:43:43] file uploaded, file id: b6c06579-2523-4db6-a795-92d60a609657
[2024-04-30 11:43:54] answer: 为了描述和总结表格中的数据，首先需要明确表格的内容和目的。你可以使用以下步骤来进行描述和总结：

1. **理解数据**：首先，仔细阅读表格中的数据，了解每列和每行的含义。确定表格的主要目的和展示的信息。
2. **确定关键指标**：从表格中找出关键指标或重要数据。这些指标可能是销售额、增长率、市场份额等，根据表格的目的和内容来确定。
3. **描述数据**：使用简洁明了的语言描述表格中的数据。可以描述数据的整体趋势、分布或异常值等。
4. **总结数据**：在描述的基础上，对数据进行总结。可以指出数据的主要特点、亮点或需要关注的问题。
5. **使用图表辅助**：如果可能的话，可以使用图表来辅助描述和总结数据。图表可以更直观地展示数据的趋势和关系。

请注意，具体的描述和总结方式可能因表格的内容和目的而有所不同。如果你能提供具体的表格或数据示例，我可以为你提供更具体的建议。


Time: 00:12.963, Memory: 8.00 MB
```

## 方法列表

* newConversation()
* uploadFile($filePath, $conversationId)
* talk($conversationId, $query, $fileId)
* [deprecated] talkStream($conversationId, $query, $fileId)

> 其中 talkStream() 方法暂时废弃，并没有很好的解决 stream 模式下的数据乱序问题，请使用 talk() 方法。

有能力和精力的话，希望有人可以帮忙重新实现这个方法。