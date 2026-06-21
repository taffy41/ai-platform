CHANGELOG
=========

0.11
----

 * Accept a full base URL with scheme (a bare host still defaults to `https`) and tolerate a trailing slash, instead of rejecting any base URL that contains a protocol

0.10
----

 * Throw a clear exception when a non-streaming Responses response is incomplete or yields no content, instead of building an empty `MultiPartResult`

0.8
---

 * [BC BREAK] Rename `PlatformFactory` to `Factory` with explicit `createProvider()` and `createPlatform()` methods

0.6
---

 * Switch to OpenResponses contract

0.2
---

 * Support for Whisper verbose transcription

0.1
---

 * Add the bridge
