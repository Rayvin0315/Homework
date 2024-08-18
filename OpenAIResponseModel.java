package com.example.iot_chatbot_practice;
import com.google.gson.annotations.SerializedName;

public class OpenAIResponseModel {

    // 回覆的唯一ID
    @SerializedName("id")
    private String id;

    // 物件類型，通常是 "response"
    @SerializedName("object")
    private String objectType;

    // 回覆的創建時間戳記
    @SerializedName("created")
    private long createdTimestamp;

    // 使用的模型版本
    @SerializedName("model")
    private String modelVersion;

    // 回覆中的choices數組，包含API的回覆文本
    @SerializedName("choices")
    private OpenAIChoice[] choices;

    // 回覆中的使用信息
    @SerializedName("usage")
    private Usage usageInfo;

    // 取得回覆的唯一ID
    public String getId() {
        return id;
    }

    // 取得物件類型
    public String getObjectType() {
        return objectType;
    }

    // 取得回覆的創建時間戳記
    public long getCreatedTimestamp() {
        return createdTimestamp;
    }

    // 取得使用的模型版本
    public String getModelVersion() {
        return modelVersion;
    }

    // 取得回覆中的選擇（choices）數組
    public OpenAIChoice[] getChoices() {
        return choices;
    }

    // 取得回覆中的使用信息
    public Usage getUsageInfo() {
        return usageInfo;
    }
}

// 定義OpenAIChoice類別，用於表示回覆中的選擇
class OpenAIChoice {

    // 選擇中的消息
    @SerializedName("message")
    private ResponseMessage message;

    // 選擇的完成原因
    @SerializedName("finish_reason")
    private String finishReason;

    // 取得選擇中的消息
    public ResponseMessage getMessage() {
        return message;
    }

    // 取得選擇的完成原因
    public String getFinishReason() {
        return finishReason;
    }
}

// 定義ResponseMessage類別，用於表示回覆中的消息
class ResponseMessage {

    // 消息的角色，通常是 "user" 或 "assistant"
    @SerializedName("role")
    private String role;

    // 消息的內容，包括用戶或助手的回應
    @SerializedName("content")
    private String content;

    @SerializedName("function_call")
    private FunctionCall functionCall;

    // 取得消息的角色
    public String getRole() {
        return role;
    }

    // 取得消息的內容
    public String getContent() {
        return content;
    }

    public FunctionCall getFunctionCall() {
        return functionCall;
    }

}

class FunctionCall {

    @SerializedName("name")
    private String name;

    @SerializedName("arguments")
    private String arguments;

    public String getName() {
        return name;
    }

    public String getArguments() {
        return arguments;
    }
}



// 定義Usage類別，用於表示回覆中的使用信息
class Usage {

    // 提示令牌的數量
    @SerializedName("prompt_tokens")
    private int promptTokens;

    // 完成令牌的數量
    @SerializedName("completion_tokens")
    private int completionTokens;

    // 總令牌的數量
    @SerializedName("total_tokens")
    private int totalTokens;

    // 取得提示令牌的數量
    public int getPromptTokens() {
        return promptTokens;
    }

    // 取得完成令牌的數量
    public int getCompletionTokens() {
        return completionTokens;
    }

    // 取得總令牌的數量
    public int getTotalTokens() {
        return totalTokens;
    }
}